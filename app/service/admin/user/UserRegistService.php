<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 会员登录
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

declare (strict_types=1);

namespace app\service\admin\user;

use app\job\SalesmanUpgradeJob;
use app\model\salesman\SalesmanCustomer;
use app\model\user\User;
use app\service\common\BaseService;
use exceptions\ApiException;
use Fastknife\Utils\RandomUtils;
use utils\Config;
use utils\TigQueue;
use utils\Time;
use utils\Util;

/**
 * 会员登录服务类
 */
class UserRegistService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 注册会员
     *
     * @param int $id
     * @return User
     */
    public function regist($params = []): User
    {
        if (!empty(Config::get('shopRegClosed'))) {
            throw new ApiException(Util::lang('商城暂不开放注册！'));
        }
        if (empty($params['username'])) {
            throw new ApiException(Util::lang('会员名称不能为空'));
        }
        if ($this->checkUsernameRegisted($params['username']) === true) {
            throw new ApiException(Util::lang('该会员名称已被注册'));
        }
        if (isset($params['mobile']) && $this->checkUserMobileRegisted($params['mobile']) === true) {
            throw new ApiException(Util::lang('该手机号已被注册'));
        }
        if (isset($params['email']) && $this->checkUserEmailRegisted($params['email']) === true) {
            throw new ApiException(Util::lang('该邮箱已被注册'));
        }
        $data = [
            'username' => $params['username'],
            'mobile' => $params['mobile'] ?? '',
            'email' => $params['email'] ?? '',
            'password' => password_hash((string)$params['password'], PASSWORD_DEFAULT),
            'avatar' => isset($params['avatar']) ? $params['avatar'] : '',
            'nickname' => isset($params['nickname']) ? $params['nickname'] : 'USER_' . RandomUtils::getRandomCode(8, 3),
            'reg_time' => Time::now(),
            'referrer_user_id' => isset($params['referrer_user_id']) ? $params['referrer_user_id'] : 0, //推荐人
			'mobile_validated' => (isset($params['mobile']) && !empty($params['mobile'])) ? 1 : 0,
			'email_validated' => (isset($params['email']) && !empty($params['email'])) ? 1 : 0,
        ];
        $user = new User();
        $user->save($data);
        if (!empty($params['salesman_id'])) {
            SalesmanCustomer::create([
                'salesman_id' => $params['salesman_id'],
                'user_id' => $user->user_id
            ]);
            app(TigQueue::class)->push(SalesmanUpgradeJob::class, [
                'salesmanId' => $params['salesman_id']
            ]);
        }
        return $user;
    }

    /**
     * 检查会员名称是否已注册
     *
     * @param [string] $username
     * @return bool
     */
    public function checkUsernameRegisted(string $username): bool
    {
        $count = User::where('username', $username)->count();
        return $count > 0;
    }

    /**
     * 检查会员手机号是否已注册
     *
     * @param [string] $mobile
     * @return bool
     */
    public function checkUserMobileRegisted(string $mobile): bool
    {
        $count = User::where('mobile', $mobile)->count();
        return $count > 0;
    }

    /**
     * 检查会员邮箱是否已注册
     *
     * @param [string] $email
     * @return bool
     */
    public function checkUserEmailRegisted(string $email): bool
    {
        $count = User::where('email', $email)->count();
        return $count > 0;
    }

    /**
     * 生成用户名
     * @return string
     */
    public function generateUsername(): string
    {
        while (true) {
            $username = Config::get('usernamePrefix') . RandomUtils::getRandomCode(8, 3);
            if (!User::where('username', $username)->count()) {
                return $username;
            }
        }
    }

    /**
     * 手机号注册会员
     * @param string $mobile
     * @param string $password
     * @param int $referrer_user_id
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function registerUserByMobile(string $mobile, string $password = '', int $referrer_user_id = 0): array
    {
        //检测手机号是否存在
        $user = app(UserService::class)->getUserByMobile($mobile);
        if (empty($user)) {
            try {
                $shop_reg_closed = Config::get('shopRegClosed');
                if ($shop_reg_closed == 1) {
                    throw new ApiException(Util::lang('商城已停止注册！'));
                }
                $username = $this->generateUsername();
                $password = $password ?? RandomUtils::getRandomCode(8);
                $register = [
                    'username' => $username,
                    'password' => $password,
                    'mobile' => $mobile,
                    'referrer_user_id' => $referrer_user_id,
                ];
                $user = $this->regist($register)->toArray();
            } catch (\Exception $e) {
                throw new ApiException(Util::lang($e->getMessage()));
            }
        }

        return $user;
    }

}
