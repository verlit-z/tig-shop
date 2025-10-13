<?php

namespace utils;

use exceptions\ApiException;
use utils\Config as UtilsConfig;
use Volc\Base\V4Curl;

class Translate extends V4Curl
{
    protected $apiList = [
        "LangDetect" => [
            "url" => "/",
            "method" => "post",
            "config" => [
                "query" => [
                    "Action" => "LangDetect",
                    "Version" => "2020-06-01",
                ],
            ],
        ],
        "TranslateText" => [
            "url" => "/",
            "method" => "post",
            "config" => [
                "query" => [
                    "Action" => "TranslateText",
                    "Version" => "2020-06-01",
                ],
            ],
        ],
    ];

    protected function getConfig(string $region)
    {
        return [
            "host" => "https://translate.volcengineapi.com",
            "config" => [
                "timeout" => 5.0,
                "headers" => [
                    "Accept" => "application/json"
                ],
                "v4_credentials" => [
                    "region" => "cn-north-1",
                    "service" => "translate",
                ],
            ],
        ];
    }

    public function langDetect(array $textList): array
    {
        $req = array('TextList' => $textList);
        try {
            $resp = $this->request('LangDetect', ['json' => $req]);
        } catch (\Throwable $e) {
            throw $e;
        }
        if ($resp->getStatusCode() != 200) {
            throw new Exception("failed to detect language: status_code=%d, resp=%s", $resp->getStatusCode(),
                $resp->getBody());
        }
        return json_decode($resp->getBody()->getContents(), true)["DetectedLanguageList"];
    }

    public function translateText(string $sourceLanguage, string $targetLanguage, array $textList): array
    {
        if (UtilsConfig::get('langOn') != 1) {
            throw new ApiException('请开启多语言配置！');
        }
        $key = UtilsConfig::get('langVolcengineAccessKey');
        $secret = UtilsConfig::get('langVolcengineSecret');
        if (empty($key) || empty($secret)) {
            throw new ApiException('请配置火山密钥');
        }
        $this->setAccessKey($key);
        $this->setSecretKey($secret);
        $req = array('SourceLanguage' => $sourceLanguage, 'TargetLanguage' => $targetLanguage, 'TextList' => $textList);
        try {
            $resp = $this->request('TranslateText', ['json' => $req]);
        } catch (\Throwable $e) {
            throw $e;
        }
        if ($resp->getStatusCode() != 200) {
            throw new ApiException(sprintf("failed to translate: status_code=%d, resp=%s",  $resp->getStatusCode(),$resp->getBody()));
        }
        $return = json_decode($resp->getBody()->getContents(), true)["TranslationList"];
        if (is_array($return)) {
            foreach ($return as $key => &$value) {
                $value['Translation'] = rtrim($value['Translation'], ".");
            }
        }
        return $return;
    }
}