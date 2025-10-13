<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 地区
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
use app\service\admin\oauth\UserAuthorizeService;
use app\service\admin\user\UserRegistService;
use app\service\admin\user\UserService;
use app\service\front\common\SocialiteService;
use think\App;
use think\Response;
use utils\Config as UtilsConfig;

/**
 * oauth控制器
 */
class Oauth extends IndexBaseController
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
     * 获得链接
     *
     * @return Response
     */
    public function render($source): Response
    {
        $redirect = request()->get('redirectUri');
        $url = (new SocialiteService($source, $redirect))->getAuthUrl();
        return $this->success($url);
    }


    /**
     * 回调并登录
     * @param $source
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function callback($source): Response
    {
        $code = $this->request->all('code');
        $redirect = request()->get('redirectUri');
        $user = (new SocialiteService($source, $redirect))->userFromCode($code);
        if (empty($user->getId())) {
            return $this->error('获取用户信息失败');
        }
        $openid = $user->getId();
        $userAuth = app(UserAuthorizeService::class)->getUserAuthorizeByOpenId($user->getId(),
            UserAuthorize::getAuthorizeTypeIdByName($source));
        if (empty($userAuth)) {
            $user = app(UserRegistService::class)->regist([
                'username' => 'User_' . rand(100000000000, 900000000000),
                'nickname' => $user->getNickname(),
                'password' => rand(100000000000, 900000000000),
            ]);
            $user_id = $user->user_id;
            app(UserAuthorizeService::class)->updateUserAuthorizeInfo($user_id, $openid,
                UserAuthorize::getAuthorizeTypeIdByName($source));
        } else {
            $user_id = $userAuth->user_id;
        }
        app(UserService::class)->setLogin($user_id);
        $token = app(UserService::class)->getLoginToken($user_id);
        return $this->success([
            'type' => 1,
            'token' => $token,
        ]);
    }


}
