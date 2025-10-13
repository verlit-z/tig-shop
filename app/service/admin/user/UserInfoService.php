<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 会员
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\user;

use app\model\order\Order;
use app\model\user\RankGrowthLog;
use app\model\user\User;
use app\model\user\UserRank;
use app\model\user\UserRankLog;
use app\service\admin\common\email\EmailService;
use app\service\admin\common\sms\SmsService;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Cache;
use utils\Time;
use utils\Util;

/**
 * 会员服务类
 */
class UserInfoService extends BaseService
{
    protected int $id;

    public function __construct(int $user_id)
    {
        $this->id = $user_id;
    }

    /**1
     * @param int $id
     * @return object
     * @throws ApiException
     * 用户中心首页数据集合
     */
    public function getUserIndex(): object
    {
        $result = User::with(['userRank' => function ($query) {
            $query->field('rank_id, rank_name, rank_ico');
        }])
            ->field('user_id,username,nickname,avatar,rank_id,balance,points,mobile_validated,email_validated,is_svip,is_company_auth')->append(['dim_username'])->find($this->id);

        if (!$result) {
            throw new ApiException(Util::lang('会员不存在'));
        }

        if ($result->mobile_validated && $result->email_validated) {
            $result->security_lv = 3;
        } elseif (($result->mobile_validated && !$result->email_validated) || (!$result->mobile_validated && $result->email_validated)) {
            $result->security_lv = 2;
        } else {
            $result->security_lv = 1;
        }

        $result->await_pay = Order::where('user_id', $this->id)->awaitPay()->where("is_del",0)->count();
        $result->await_shipping = Order::where('user_id', $this->id)->awaitShip()->where("is_del",0)->count();
        $result->await_received = Order::where('user_id', $this->id)->awaitReceived()->where("is_del",0)->count();
        $result->await_comment = Order::where('user_id', $this->id)->awaitComment()->where("is_del",0)->count();
        $result->await_coupon = app(UserCouponService::class)->getUserNormalCouponCount($this->id);

        return $result;
    }

    /**
     * 获取简单的详情详情
     *
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function getSimpleBaseInfo(): array
    {
        $result = User::field('user_id,username,nickname,avatar,points,balance,frozen_balance,birthday,mobile,email,history_product_ids')
            ->append(['dim_username'])->find($this->id);

        if (!$result) {
            throw new ApiException(Util::lang('会员不存在'));
        }
        return $result->toArray();
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function getBaseInfo(): array
    {
        $rank_config = app(UserRankService::class)->getRankConfig();
        $growth_info = $this->refreshRank();

        $result = User::with(['userRank'])->field('user_id,username,nickname,avatar,points,balance,frozen_balance,birthday,mobile,email,rank_id,wechat_img,is_company_auth')
            ->append(['dim_username'])->find($this->id);

        if (!$result) {
            throw new ApiException(Util::lang('会员不存在'));
        }

        $result->total_balance = Util::number_format_convert($result->balance + $result->frozen_balance);
        $result->avatar = app(UserService::class)->getUserAvatar($result->avatar);
        $result->coupon = app(UserCouponService::class)->getUserNormalCouponCount($this->id);
        // 获取会员等级有效期
        $result->rank_expire_time = $growth_info['rank_expire_time'] ?? 0;
        $result->growth = $growth_info['growth'] ?? 0;
        $result->growth_points = $growth_info['growth_points'] ?? 0;
        // 会员等级配置规则
        $result->rank_config = $rank_config;
        return $result->toArray();
    }

    /**
     * 刷新会员等级及返回成长信息
     * @return array
     */
    public function refreshRank(): array
    {
        $rank_config = app(UserRankService::class)->getRankConfig();
        if (!empty($rank_config)) {
            // 根据成长值判断等级
            $growth = app(UserRankService::class)->getExpireRangeGrowth($this->id);
            $user = User::find($this->id);
            $user_rank = UserRank::find($user->rank_id);
            if (empty($user_rank)) {
                // 有等级配置且该用户未设置等级的设置为默认等级
                $min_rank = UserRank::where('min_growth_points',0)->order('rank_id','asc')->find();
                if (!empty($min_rank)) {
                    $user->rank_id = $min_rank['rank_id'];
                    if ($user->save()) {
                        // 记录等级变更记录
                        $rank_log = [
                            'user_id' => $this->id,
                            'rank_id' => $min_rank->rank_id,
                            'rank_type' => $min_rank->rank_type,
                            'rank_name' => $min_rank->rank_name,
                        ];
                        UserRankLog::create($rank_log);
                    }
                }
            }
            if (!empty($rank_config['data'])) {

                $user_rank_log = UserRankLog::where('user_id',$this->id)->order("id","DESC")->find();
                if (!empty($user_rank_log)) {
                    // 有时效
                    $rank_expire_time = Time::format(strtotime('+' . $rank_config['data']['rankAfterMonth'] . ' months',
                        Time::toTime($user_rank_log['change_time'])));
                    // 等级过期之后重新定义等级
                    if (Time::now() >= Time::toTime($rank_expire_time)) {
                        app(UserRankService::class)->getGrowthByRule($this->id);
                        // 重新定义有效期
                        $new_rank_log = UserRankLog::where('user_id',$this->id)->order("id","DESC")->find();
                        $rank_expire_time = Time::format(strtotime('+' . $rank_config['data']['rankAfterMonth'] . ' months',
                            Time::toTime($new_rank_log->change_time)));
                    }
                }
                app(UserRankService::class)->modifyUserRank($this->id);
            }


        }

        return [
            'growth' => $growth ?? 0,
            'rank_expire_time' => $rank_expire_time ?? 0,
            'growth_points' => $user['growth_points']?? 0,
        ];
    }


    /**
     * 修改个人信息
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateInformation(array $data)
    {
        $user = User::findOrEmpty($this->id);
        if (empty($user->toArray())) {
            throw new ApiException(/** LANG */Util::lang('会员不存在'));
        }

        $this->getGrowthPoints(1);

        if ($user->save($data)) {
            return true;
        }
        return false;
    }

    /**
     * 修改密码 / 支付密码
     * @param array $data
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function modifyPassword(array $data): bool
    {
        $user = User::find($this->id);

        if (empty($user)) {
            throw new ApiException(/** LANG */Util::lang('会员不存在'));
        }

        // 用户密码
        $result = $user->save(['password' => password_hash($data["password"], PASSWORD_DEFAULT)]);

        return $result !== false;
    }

    /**
     * 手机验证 / 手机绑定
     * @param string $mobile
     * @param int $code
     * @return bool
     * @throws ApiException
     */
    public function mobileValidate(string $mobile, int $code, int $type, string $event): bool
    {
        if (empty($mobile)) {
            throw new ApiException(/** LANG */Util::lang('手机号不能为空'));
        }
        if (empty($code)) {
            throw new ApiException(/** LANG */Util::lang('请输入验证码'));
        }
        if (app(SmsService::class)->checkCode($mobile, $code, $event) == false) {
            throw new ApiException(/** LANG */Util::lang('短信验证码错误或已过期，请重试'));
        }
        // 绑定手机
        if (User::where("mobile", $mobile)->where("user_id", "<>", $this->id)->count()) {
            throw new ApiException(/** LANG */Util::lang('该手机号已被其他会员绑定,请更换手机号,或联系客服申诉'));
        }

        if ($type) {
            // 第一次绑定手机号获得成长值
            $this->getGrowthPoints(2, ['mobile' => $mobile]);
            User::find($this->id)->save(['mobile' => $mobile, 'mobile_validated' => 1]);
        }

        return true;
    }

    /**
     * 邮箱验证 / 邮箱绑定
     * @param string $email
     * @param int $type
     * @return true
     * @throws ApiException
     */
    public function emailValidate(string $email, int $type)
    {
        if (empty($email)) {
            throw new ApiException(/** LANG */Util::lang('邮箱不能为空'));
        }
        // 绑定邮箱
        if (User::where("email", $email)->where("user_id", "<>", $this->id)->count()) {
            throw new ApiException(/** LANG */Util::lang('该邮箱已被其他会员绑定,请更换邮箱,或联系客服申诉'));
        }
        if ($type) {
            User::find($this->id)->save(['email' => $email, 'email_validated' => 1]);
        }
        return true;
    }

    /**
     * 邮箱验证 / 邮箱绑定
     * @param string $mobile
     * @param int $code
     * @return bool
     * @throws ApiException
     */
    public function emailValidateNew(string $email,string $code, int $type, string $event): string
    {
        if (empty($email)) {
            throw new ApiException(/** LANG */Util::lang('邮箱不能为空'));
        }
        if (empty($code)) {
            throw new ApiException(/** LANG */Util::lang('请输入验证码'));
        }

        if(!app(EmailService::class)->checkCode($email, $code, $event)){
            throw new ApiException(/** LANG */Util::lang('邮箱验证码错误或已过期，请重试'));
        }

        if (User::where("email", $email)->where("user_id", "<>", $this->id)->count()) {
            throw new ApiException(/** LANG */Util::lang('该邮箱已被其他会员绑定,请更换邮箱,或联系客服申诉'));
        }

        if ($type) {
            // 第一次绑定手机号获得成长值
            //$this->getGrowthPoints(2, ['email' => $email]);
            User::find($this->id)->save(['email' => $email, 'email_validated' => 1]);
        }
        return true;
    }


    /**
     * 修改头像
     * @return bool
     * @throws ApiException
     */
    public function modify_avatar(string $avatar): bool
    {
        return User::find($this->id)->save(['avatar' => $avatar]);
    }

	/**
	 * 添加浏览记录
	 * @param int $product_id
	 * @param int $user_id
	 * @return bool
	 * @throws ApiException
	 */
	public function historyProductRecord(int $product_id): bool
	{
		try {
			$user = User::find($this->id);
			$history_product_ids = $user->history_product_ids ?? [];
            if (in_array($product_id,$history_product_ids)) {
                // 存在则将该商品移到最前面
                $key = array_search($product_id,$history_product_ids);
                unset($history_product_ids[$key]);
            }
			array_unshift($history_product_ids, $product_id);
            // 保持数组唯一性并限制最大数量为20
            if (count($history_product_ids) > 20) {
                $history_product_ids = array_slice(array_unique($history_product_ids), 0, 20);
            } else {
                $history_product_ids = array_unique($history_product_ids);
            }
            $user->history_product_ids = $history_product_ids;
            $user->save();
		} catch (\Exception $e) {

		}
		return true;
	}

    /**
     * 获取会员成长值
     * @param int $type
     * @param array $data
     * @return bool
     */
    public function getGrowthPoints(int $type = 0, array $data = []):bool
    {
        $user = User::findOrEmpty($this->id);
        if (empty($user->toArray())) {
            throw new ApiException(/** LANG */Util::lang('会员不存在'));
        }

        $growth_points_config = app(UserRankService::class)->getGrowConfig();
        $growth_points = 0;
        if (!empty($growth_points_config)) {
            switch ($type) {
                case 1:
                    // 第一次修改信息赠送成长值
                    if (isset($growth_points_config['evpi']) && $growth_points_config['evpi']) {
                        if (!empty($data)) {
                            if (empty($user->nickname) && !empty($data['nickname']) ||
                                ($user->birthday == '0000-00-00' || empty($user->birthday))
                                && !empty($data['birthday'])) {
                                $growth_points = $growth_points_config['evpiGrowth'];
                            }
                        }
                    }
                    break;
                case 2:
                    // 第一次绑定手机号
                    if (isset($growth_points_config['bindPhone']) && $growth_points_config['bindPhone']) {
                        if (!empty($data)) {
                            if (empty($user->mobile) && !empty($data['mobile'])) {
                                // 绑定手机号可以获得成长值
                                $growth_points = $growth_points_config['bindPhoneGrowth'];
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        if ($growth_points) {
            // 记录成长值日志
            $growth_log = [
                'user_id' => $this->id,
                'type' => $type == 1 ? RankGrowthLog::GROWTH_TYPE_INFORMATION : RankGrowthLog::GROWTH_TYPE_BIND_PHONE,
                'growth_points' => $growth_points,
                'change_type' => 1
            ];
            RankGrowthLog::create($growth_log);
            //原来只增加了记录 没有去增加用户表的成长值
            User::where('user_id',$this->id)->inc('growth_points',$growth_points)->save();
            $user_growth_points = User::where('user_id',$this->id)->value('growth_points');
            app(UserRankService::class)->changeUserRank($user_growth_points,$this->id);
        }
        return true;
    }

    public function closeUser(): bool
    {
        $user = User::findOrEmpty($this->id);
        if (empty($user->toArray())) {
            throw new ApiException(/** LANG */Util::lang('会员不存在'));
        }
        $user->status = 0;
        $user->save();
        return true;
    }
}
