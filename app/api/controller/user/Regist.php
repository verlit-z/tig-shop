<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 会员注册
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\captcha\CaptchaService;
use app\service\admin\common\email\EmailService;
use app\service\admin\common\sms\SmsService;
use app\service\admin\user\UserRegistService;
use app\service\admin\user\UserService;
use exceptions\ApiException;
use think\App;
use utils\Config;
use utils\Util;

/**
 * 会员登录控制器
 */
class Regist extends IndexBaseController
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
     * 会员注册操作
     *
     * @return \think\Response
     */
    public function registAct(): \think\Response
    {
        $data = $this->request->only([
            'regist_type' => 'mobile',
            'username' => '',
            'password' => '',
            'mobile' => '',
            'mobile_code' => '',
            'email' => '',
            'email_code' => '',
            'salesman_id' => 0,
        ], 'post');
        $salesman_id = $data['salesman_id'];
        $shop_reg_closed = Config::get('shopRegClosed');
        if ($shop_reg_closed == 1) {
            $this->error(Util::lang('商城已停止注册！'));
        }
        $regist_type = $data['regist_type'];
        $password = $data['password'];
        $referrer_user_id = $this->request->all('referrer_user_id/d', 0);
        $username = app(UserRegistService::class)->generateUsername();
        if ($regist_type == 'mobile') {
            // 手机号注册
            $mobile = $this->request->all('mobile', '');
            $mobile_code = $this->request->all('mobile_code', '');
            if (empty($mobile)) {
                return $this->error(Util::lang('手机号不能为空'));
            }
            if (empty($mobile_code)) {
                return $this->error(Util::lang('短信验证码不能为空'));
            }
            if (app(SmsService::class)->checkCode($mobile, $mobile_code) == false) {
                throw new ApiException(Util::lang('短信验证码错误或已过期，请重试'));
            }
            $data = [
                'username' => $username,
                'password' => $password,
                'mobile' => $mobile,
                'referrer_user_id' => $referrer_user_id,
                'salesman_id' => $salesman_id,
            ];
        } elseif ($regist_type == 'email') {
            // 邮箱注册
            $email = $this->request->all('email', '');
            $email_code = $this->request->all('email_code', '');
            if (empty($email)) {
                return $this->error(Util::lang('邮箱不能为空'));
            }
            if (empty($email_code)) {
                return $this->error(Util::lang('邮箱验证码不能为空'));
            }
            if (app(EmailService::class)->checkCode($email, $email_code, 'register_code') == false) {
                throw new ApiException(Util::lang('邮箱验证码错误或已过期，请重试'));
            }
            $data = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'referrer_user_id' => $referrer_user_id,
            ];
        }

        try {
            $user = app(UserRegistService::class)->regist($data);

        } catch (\Exception $e) {
            return $this->error(Util::lang($e->getMessage()));
        }
        if (!$user) {
            return $this->error(Util::lang('注册失败'));
        }
        // 设置登录状态
        app(UserService::class)->setLogin($user->user_id);

        $token = app(UserService::class)->getLoginToken($user->user_id);
        return $this->success($token);
    }

	/**
	 * 获取邮箱验证码
	 * @return \think\Response
	 * @throws ApiException
	 */
	public function sendEmailCode(): \think\Response
    {
		$email = $this->request->all('email', '');
		if (!$email) {
			return $this->error(Util::lang('邮箱不能为空'));
		}
		// 行为验证码
		app(CaptchaService::class)->setTag('emailCode:' . $email)
			->setToken($this->request->all('verify_token', ''))
			->verification();

		try {
			app(EmailService::class)->sendEmailCode($email, 'register_code');
			return $this->success(Util::lang('发送成功！'));
		} catch(\Exception $e) {
			return $this->error(Util::lang('发送失败！') . $e->getMessage());
		}
    }

}
