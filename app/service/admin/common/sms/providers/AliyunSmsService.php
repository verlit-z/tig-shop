<?php

namespace app\service\admin\common\sms\providers;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Darabonba\OpenApi\Models\Config as OpenApiConfig;
use exceptions\ApiException;
use think\Exception;
use think\facade\Log;
use utils\Config;

class AliyunSmsService
{
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $signName;

    protected string $orgPath;

    public function __construct()
    {
        $this->accessKeyId = Config::get('smsKeyId');
        $this->accessKeySecret = Config::get("smsKeySecret");
        $this->signName = Config::get("smsSignName");
        if (empty($this->accessKeyId)) {
            throw new ApiException('短信配置错误，缺少#keyId');
        }
        if (empty($this->accessKeySecret)) {
            throw new ApiException('短信配置错误，缺少#keySecret');
        }
        if (empty($this->signName)) {
            throw new ApiException('短信配置错误，缺少#signName');
        }
    }
    protected function createClient()
    {
        $config = new OpenApiConfig([
            "accessKeyId" => $this->accessKeyId,
            "accessKeySecret" => $this->accessKeySecret,
        ]);
        // Endpoint 请参考 https://api.aliyun.com/product/Dysmsapi
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }

    /**
     * 发送短信
     *
     * @param [string] $mobile
     * @param [string] $template_code
     * @param [string] $content
     * @return void
     */
    public function sendSms(string $mobile, string $template_code, array $template_param): bool
    {
        $client = $this->createClient();
        $config = [
            "phoneNumbers" => $mobile,
            "signName" => $this->signName,
            "templateCode" => $template_code,
        ];
        if ($template_param) {
            $config['templateParam'] = json_encode($template_param);
        }
        $sendSmsRequest = new SendSmsRequest($config);
        try {
            // 复制代码运行请自行打印 API 的返回值
            $res = $client->sendSmsWithOptions($sendSmsRequest, new RuntimeOptions([]));
            if ($res->body->code != 'OK'){
                Log::info("开始发送短信3" . $res->body->message);
                throw new ApiException($res->body->message);
            }
        } catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            Log::info("开始发送短信4" . $error->message);
            throw new ApiException($error->message);
        }

        // 发起访问请求
        // $acsResponse = $this->acsClient->getAcsResponse($request);
        return true;
    }
}
