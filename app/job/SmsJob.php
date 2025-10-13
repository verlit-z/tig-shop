<?php

namespace app\job;


use app\service\admin\common\sms\SmsService;
use think\facade\Log;

/**
 * 发送短信队列
 */
class SmsJob extends BaseJob
{

    /**
     * @param $data [mobile,template_code,content]
     * @return bool
     */
    public function doJob($data): bool
    {
        try {
            $smsService = new SmsService();
            Log::info("开始发送短信1");
            if (empty($data['mobile'])) return false;
            Log::info("开始发送短信2");
            $smsService->createSmsService()->sendSms($data['mobile'], $data['template_code'], $data['content']);
            return true;
        } catch (\Exception $e) {
            Log::info("短信错误");
            Log::info($e->getMessage() . $e->getTraceAsString() . $e->getCode() . $e->getLine() . $e->getFile());
            return false;
        }
    }
}