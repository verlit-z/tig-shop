<?php

namespace app\service\admin\common\sms;

use app\service\admin\common\sms\providers\AliyunSmsService;
use app\service\admin\setting\MessageTemplateService;
use exceptions\ApiException;
use think\facade\Cache;
use utils\TigQueue;

class SmsService
{
    /**
     * 短信过期时间
     *
     * @var integer
     */
    private int $expireTime = 120;

    /**
     * 架构方法
     */
    public function __construct()
    {

    }

    /**
     * 通用发送短信
     *
     * @param string $mobile
     * @param string $type
     * @param string $content
     * @return bool
     */
    public function sendSms(string $mobile, string $type, string | array $content): bool
    {
        if (empty($mobile)) {
            throw new ApiException('手机号不能为空');
        }


//        $mobile = ltrim($mobile, '86');
        // 获取模板
        $content = is_array($content) ? $content : explode(',', $content);
        $content_arr = $this->getTemplate($type, $content);
        if (empty($content_arr['template_code'])) {
            throw new ApiException('消息模板配置参数错误');
        }
        if (!$content_arr) {
            throw new ApiException('短信模板错误');
        }
        $template_code = $content_arr['template_code'];
        $content = $content_arr['content'];
        app(TigQueue::class)->push('app\job\SmsJob',
                ['mobile' => $mobile, 'template_code' => $template_code, 'content' => $content]);
        return true;
        // 发送短信
//        try {
//            $this->createSmsService()->sendSms($mobile, $template_code, $content);
//        } catch (\Exception $e) {
//            throw new ApiException($e->getMessage());
//        }
        return true;
    }

    /**
     * 发送短信验证码
     *
     * @param string $mobile
     * @param string $event 事件默认login事件
     * @return bool
     */
    public function sendCode(string $mobile, string $event = 'login'): bool
    {
        return $this->sendSms($mobile, 'code', $this->creatCode($mobile,$event));
    }

    public function createSmsService()
    {
        // 暂只支持aliyun
        $provider = 'aliyun';

        switch ($provider) {
            case 'aliyun':
                return new AliyunSmsService();
            case 'tencent':
            default:
                throw new \Exception("#provider error");
        }
    }

    /**
     * 生成验证码
     *
     * @param string $mobile
     * @param string $event 事件默认login事件
     * @return string
     */
    public function creatCode(string $mobile, string $event = 'login'): string
    {
        $code = rand(100000, 999999);
        Cache::set($event.'mobileCode:' . $mobile, $code, $this->expireTime);
        return $code;
    }

    /**
     * 验证短信验证码
     *
     * @param string $mobile
     * @param string $code
     * @param string $event 事件默认login事件
     * @return boolean
     */
    public function checkCode(string $mobile, string $code, string $event = 'login'): bool
    {
        return Cache::get($event.'mobileCode:' . $mobile) === $code;
    }

    /**
     * 获取短信模板相关内容信息 todo
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getTemplate(string $type, array $params = []): array
    {
        $res = ['template_code' => '', 'content' => []];
        switch ($type) {
            case "code":
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(8);
                break;
            case 'user_order':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(1);
                break;
            case 'user_pay':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(2);
                break;
            case 'user_shipping':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(3);
                break;
            case 'shop_order':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(5);
                break;
            case 'shop_pay':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(6);
                break;
            case 'invoice':
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(7);
                break;
            case "user_certification":
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(9);
                break;
            case "merchant_apply_refuse":
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(11);
                break;
            case "merchant_apply_success":
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(10);
                break;
            case "refund_apply_success":
                $template_info = app(MessageTemplateService::class)->getMessageTemplateList(4);
                break;
        }
        if (isset($template_info['type_info']) && $template_info['type_info']['is_msg'] == 1 && isset($template_info['msg']) && !empty($template_info['msg']['info']['template_id'])) {
            $res['template_code'] = $template_info['msg']['info']['template_id'];
            $res['content'] = ['code' => $params[0]];
            if (in_array($type, ['user_pay'])) $res['content'] = ['order' => $params[0]];
            if($type == 'user_order') $res['content'] = ['order_sn' => $params[0]];
            if ($type == 'user_shipping') $res['content'] = ['shipping_name' => $params[0],  'code' => $params[1]];
            //if ($type == 'invoice') $res['content'] = ['no' => $params[0], 'fee' => number_format($params[1], 2)];
            if (in_array($type, ['shop_order', 'shop_pay'])) $res['content'] = ['order' => $params[0], 'fee' => number_format($params[1], 2)];
            if ($type == "user_certification") {
                $res['content'] = ['type_text' => $params[0],'num' => $params[1]];
            }
            if($type == "merchant_apply_success"){
                $res['content'] = ['username' => $params[0],'password' => $params[1]];
            }
            if($type == 'refund_apply_success') {
                $res['content'] = ['order' => $params[0]];
            }
        }
        return $res;
    }

}
