<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 会员信息
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\model\user\UserAuthorize;
use app\service\admin\authority\AccessTokenService;
use app\service\admin\captcha\CaptchaService;
use app\service\admin\common\email\EmailService;
use app\service\admin\common\sms\SmsService;
use app\service\admin\image\Image;
use app\service\admin\merchant\AdminUserShopService;
use app\service\admin\oauth\UserAuthorizeService;
use app\service\admin\oauth\WechatOauthService;
use app\service\admin\product\ProductService;
use app\service\admin\salesman\SalesmanService;
use app\service\admin\user\UserInfoService;
use app\service\admin\user\UserRankService;
use app\service\front\user\CollectShopService;
use exceptions\ApiException;
use think\App;
use think\Response;
use utils\Config;
use utils\Util;

/**
 * 会员中心控制器
 */
class User extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 会员基础信息
     * @param int $id
     * @return \think\Response
     */
    public function detail(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $data = $userInfoService->getBaseInfo();
        //查询用户是否授权
        $data['is_bind_wechat'] = app(UserAuthorizeService::class)->checkUserIsAuthorize(request()->userId);
        $data['salesman'] = null;
        //查询是否开启签到
        $show_sign = Config::get('pointsSetting', '');
        $data['show_sign'] = $show_sign;
        $data['has_shop'] = null;
        return $this->success($data);
    }

    /**
     * 修改个人信息
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function updateInformation(): \think\Response
    {
        $data = $this->request->only([
            'birthday' => '',
            "nickname" => "",
            "email" => "",
            "mobile" => "",
            'wechat_img' => ''
        ], 'post');
        $userInfoService = new UserInfoService(request()->userId);
        $result = $userInfoService->updateInformation($data);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("修改失败"));
    }

    /**
     * 会员中心首页数据
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function memberCenter(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $data = $userInfoService->getUserIndex();

        return $this->success($data);
    }

    /**
     * 授权回调获取用户信息
     * @return \think\Response
     */
    public function oAuth(): \think\Response
    {
        $code = $this->request->all('code');
        $type = $this->request->all('type');
        if (empty($type) || empty($code)) {
            return $this->error(Util::lang('参数缺失！'));
        }

        switch ($type) {
            case 'wechat':
                $data = app(WechatOauthService::class)->auth($code);
                break;
            default:
                return $this->error(Util::lang('未找到授权类型！'));
        }

        return $this->success($data);
    }

    /**
     * 修改密码获取验证码
     * @throws \exceptions\ApiException
     */
    public function sendMobileCodeByModifyPassword(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $mobile = $userInfo['mobile'];
        $event = 'modify_password';
        // 行为验证码
        app(CaptchaService::class)->setTag($event . 'mobileCode:' . $mobile)
            ->setToken($this->request->all('verify_token', ''))
            ->verification();

        try {
            app(SmsService::class)->sendCode($mobile, $event);
            return $this->success(Util::lang('发送成功！'));
        } catch (\Exception $e) {
            return $this->error(Util::lang('发送失败！') . $e->getMessage());
        }
    }

    /**
     * 修改密码手机验证
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function checkModifyPasswordMobileCode(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $mobile = $userInfo['mobile'];
        $code = $this->request->all("code", "");
        $password = $this->request->all("password", "");
        if (empty($password)) {
            throw new ApiException(/** LANG */ Util::lang('新密码不能为空'));
        }
        $userInfoService = new UserInfoService(request()->userId);
        $userInfoService->mobileValidate($mobile, $code, 0, 'modify_password');
        $result = $userInfoService->modifyPassword(['password' => $password]);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 修改密码
     * @return \think\Response
     * @throws \exceptions\ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function modifyPassword(): \think\Response
    {
        $data = $this->request->only([
            'old_password' => '',
            'password' => '',
            'confirm_password' => '',
        ], 'post');
        $userInfoService = new UserInfoService(request()->userId);
        $result = $userInfoService->modifyPassword($data);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 手机修改获取验证码
     * @throws \exceptions\ApiException
     */
    public function sendMobileCodeByMobileValidate(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $mobile = $userInfo['mobile'];
        $event = 'mobile_validate';
        // 行为验证码
        app(CaptchaService::class)->setTag($event . 'mobileCode:' . $mobile)
            ->setToken($this->request->all('verify_token', ''))
            ->verification();

        try {
            app(SmsService::class)->sendCode($mobile, $event);
            return $this->success();
        } catch (\Exception $e) {
            return $this->error(Util::lang('发送失败！') . $e->getMessage());
        }
    }

    /**
     * 邮箱修改获取验证码
     * @throws \exceptions\ApiException
     */
    public function sendEmailCodeByEmailValidate(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $email = $userInfo['email'];
        $event = 'register_code';
        // 行为验证码
        app(CaptchaService::class)->setTag($event . 'emailValidateCode:' . $email)
            ->setToken($this->request->all('verify_token', ''))
            ->verification();
        try {
            app(EmailService::class)->sendEmailCode($email, $event, 'register_code');
            return $this->success();
        }catch (\Exception $e) {
            return $this->error(Util::lang('发送失败！') . $e->getMessage());
        }
    }

    /**
     * 手机修改新手机获取验证码
     * @throws \exceptions\ApiException
     */
    public function sendMobileCodeByModifyMobile(): \think\Response
    {
        $mobile = $this->request->all('mobile', '');
        if (!$mobile) {
            return $this->error(Util::lang('手机号不能为空'));
        }
        $event = 'modify_mobile';
        // 行为验证码
        app(CaptchaService::class)->setTag($event . 'mobileCode:' . $mobile)
            ->setToken($this->request->all('verify_token', ''))
            ->verification();

        try {
            app(SmsService::class)->sendCode($mobile, $event);
            return $this->success();
        } catch (\Exception $e) {
            return $this->error(Util::lang('发送失败！') . $e->getMessage());
        }
    }

    /**
     * 邮箱修改新新邮箱获取验证码
     * @throws \exceptions\ApiException
     */
    public function sendEmailCodeByModifyEmail(): \think\Response
    {
        $email = $this->request->all('email ', '');
        if (!$email) {
            return $this->error(Util::lang('邮箱号不能为空'));
        }
        $event = 'register_code';
        // 行为验证码
        app(CaptchaService::class)->setTag($event . 'emailCode:' . $email)
            ->setToken($this->request->all('verify_token', ''))
            ->verification();
        try {
            app(EmailService::class)->sendEmailCode($email, $event, 'register_code');
            return $this->success();
        } catch (\Exception $e) {
            return $this->error(Util::lang('发送失败！') . $e->getMessage());
        }
    }

    /**
     * 手机验证
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function mobileValidate(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $mobile = $userInfo['mobile'];
        $code = $this->request->all("code", "");
        $userInfoService = new UserInfoService(request()->userId);
        // 判断code数据类型
        if (!is_numeric($code)) {
            return $this->error(/** LANG */ Util::lang("验证码错误"));
        }
        $result = $userInfoService->mobileValidate($mobile, $code, 0, 'mobile_validate');
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 手机绑定
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function modifyMobile(): \think\Response
    {
        $mobile = $this->request->all("mobile", "");
        $code = $this->request->all("code", "");
        $userInfoService = new UserInfoService(request()->userId);
        // 判断code数据类型
        if (!is_numeric($code)) {
            return $this->error(/** LANG */ Util::lang("验证码错误"));
        }
        $result = $userInfoService->mobileValidate($mobile, $code, 1, 'modify_mobile');
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 邮箱绑定
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function modifyEmail(): \think\Response
    {
        $email = $this->request->all("email", "");
        $code = $this->request->all("code", "");
        $userInfoService = new UserInfoService(request()->userId);
        // 判断code数据类型
        if (!is_numeric($code)) {
            return $this->error(/** LANG */ Util::lang("验证码错误"));
        }
        $result = $userInfoService->emailValidateNew($email, $code, 1, 'register_code');
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 邮箱验证 / 邮箱绑定
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function emailValidate(): \think\Response
    {
        $email = $this->request->all("email", "");
        $type = $this->request->all("type/d", 0);
        $userInfoService = new UserInfoService(request()->userId);
        $result = $userInfoService->emailValidate($email, $type);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 邮箱验证 / 邮箱绑定
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function emailValidateNew(): \think\Response
    {
        $email = $this->request->all("email", "");
        $type = $this->request->all("code", "");
        $userInfoService = new UserInfoService(request()->userId);
        $event = 'register_code';
        $result = $userInfoService->emailValidateNew($email, $type, 1 , $event);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang("操作失败"));

    }

    /**
     * 最近浏览
     * @return \think\Response
     */
    public function historyProduct(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        $list = [];
        if (!empty($userInfo['history_product_ids'])) {
			$history_product_ids = $userInfo['history_product_ids'];
            $list = app(ProductService::class)->getFilterResult([
                'product_ids' => $history_product_ids,
                'size' => 20,
                'sort_field_raw' => "field(product_id," . implode(',', $history_product_ids) . ")",
            ]);
        }
        return $this->success($list);
    }

    /**
     * 删除最近浏览
     * @return \think\Response
     */
    public function delHistoryProduct(): \think\Response
    {
        $userInfoService = new UserInfoService(request()->userId);
        $ids = request()->post('ids', []);
        $userInfo = $userInfoService->getSimpleBaseInfo();
        if (!empty($userInfo['history_product_ids'])) {
			$history_product_ids = $userInfo['history_product_ids'];
            foreach ($ids as $id) {
                foreach ($history_product_ids as $k => $history_product_id) {
                    if ($id == $history_product_id) {
                        unset($history_product_ids[$k]);
                    }
                }
            }
            $history_product_ids = array_values($history_product_ids);
            $userInfoService->updateInformation([
                'history_product_ids' => $history_product_ids
            ]);
        }
        return $this->success();
    }

    /**
     * pc端上传文件接口
     * @return \think\Response
     * @throws ApiException
     * @throws \think\Exception
     */
    public function uploadImg(): \think\Response
    {
        if (request()->file('file')) {
            $image = new Image(request()->file('file'), 'pc');
            $original_img = $image->save();
            $thumb_img = $image->makeThumb(200, 200);
        } else {
            return $this->error(Util::lang('图片上传错误！'));
        }
        if (!$original_img || !$thumb_img) {
            return $this->error(Util::lang('图片上传错误！'));
        }
        return $this->success([
            'pic_thumb' => $thumb_img,
            'pic_url' => $original_img,
            'pic_name' => $image->orgName,
            'storage_url' => $image->getStorageUrl(),
        ]);
    }

    /**
     * 修改头像
     * @return \think\Response
     * @throws ApiException
     * @throws \think\Exception
     */
    public function modifyAvatar(): \think\Response
    {
        if (request()->file('file')) {
            $image = new Image(request()->file('file'), 'gallery');
            $original_img = $image->save();
            $thumb_img = $image->makeThumb(200, 200);
        } else {
            return $this->error(Util::lang('图片上传错误！'));
        }
        if (!$original_img || !$thumb_img) {
            return $this->error(Util::lang('图片上传错误！'));
        }
        $userInfoService = new UserInfoService(request()->userId);
        $result = $userInfoService->modify_avatar(Config::get('') . $thumb_img);
        return $result ? $this->success(/** LANG */ Util::lang("操作成功")) : $this->error(/** LANG */ Util::lang("操作失败"));
    }

    /**
     * 我收藏的店铺
     * @return \think\Response
     * @throws ApiException
     * @throws \think\Exception
     */
    public function myCollectShop(): \think\Response
    {
        $filter = $this->request->only([
            'page' => 1,
            'size' => 10,
        ], 'get');
        $userId = request()->userId;
        $service = app(CollectShopService::class);
        $filter['user_id'] = $userId;
        $list = $service->getFilterList($filter, [
            'shop' => [
                'hot_product' => function ($query) {
                    $query->limit(10);
                },
                'new_product' => function ($query) {
                    $query->limit(10);
                },
                'best_product' => function ($query) {
                    $query->limit(10);
                }
            ]
        ], [], ['product', 'collect']);
        $count = $service->getFilterCount($filter);
        return $this->success(['records' => $list, 'total' => $count]);
    }

    /**
     * 获取会员等级列表
     * @return \think\Response
     * @throws ApiException
     * @throws \think\Exception
     */
    public function levelList():Response
    {
        $filter = $this->request->only([
            'page' => 1,
            'size' => -1,
        ], 'get');
        $item = app(UserRankService::class)->getFilterList($filter);
        $config = app(UserRankService::class)->getRankConfig();
        $grow_config = app(UserRankService::class)->getGrowConfig();
        return $this->success([
            'item' => $item,
            'rank_config' => $config,
            'grow_config' => $grow_config
        ]);
    }


    /**
     * 获取用户权益信息
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function levelInfo():Response
    {
        $filter = $this->request->only([
            'rank_id/d' => 0,
        ], 'get');
        $item = app(UserRankService::class)->getRankInfo($filter['rank_id']);
        return $this->success($item);
    }

    /**
     * 退出登录
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function logout()
    {
        $userId = request()->userId;
        $res = app(AccessTokenService::class)->setApp('app')->setId($userId)->deleteToken();
        return $res ? $this->success(Util::lang('退出成功')) : $this->error(Util::lang('退出失败'));
    }

    public function close()
    {
        $userId = request()->userId;
        $userInfoService = new UserInfoService(request()->userId);
        $res = $userInfoService->closeUser();
        return $res ? $this->success(Util::lang('注销成功')) : $this->error(Util::lang('注销失败'));
    }

    public function userOpenId()
    {


        $userId = request()->userId;
        $openid = app(UserAuthorize::class)->where('user_id',$userId)->value('open_id');
        return $this->success(['openid'=>$openid]);
    }
}
