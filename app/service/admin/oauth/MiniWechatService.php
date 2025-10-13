<?php

namespace app\service\admin\oauth;

use app\service\common\BaseService;
use EasyWeChat\MiniApp\Application;
use exceptions\ApiException;
use utils\Config;
use EasyWeChat\Factory;

class MiniWechatService extends BaseService
{
    protected object|null $application = null;

    /**
     * 初始化
     * @return object|Application|null
     * @throws ApiException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function getApplication(): object
    {
        if ($this->application != null) return $this->application;
        $app_id = Config::get('wechatMiniProgramAppId');
        $app_secret = Config::get('wechatMiniProgramSecret');
        if (!$app_id || !$app_secret) {
            throw new ApiException('请先填写小程序appId和secret并保存');
        }
        $config = [
            'app_id' => $app_id,
            'secret' => $app_secret,
            'token' => 'easywechat',
            'aes_key' => '',
            'use_stable_access_token' => false,
            'http' => [
                'throw' => true,
                'timeout' => 5.0,
                'retry' => true, // 使用默认重试配置
            ],
        ];
        return new Application($config);
    }


    /**
     * 发货提醒
     * @param array $shippingData
     * @return void
     * @throws ApiException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function orderShipping(array $shippingData)
    {
        $result = app(MiniWechatService::class)->getApplication()->getClient()->post('/wxa/sec/order/upload_shipping_info',
            [
            'json' => [
                'order_key' => [
                    'order_number_type' => 1,
                    'mchid' => \utils\Config::get('wechatPayMchid'),
                    'out_trade_no' => $shippingData['out_trade_no']
                ],
                'logistics_type' => $shippingData['logistics_type'],
                'delivery_mode' => $shippingData['delivery_mode'],
                'is_all_delivered' => $shippingData['is_all_delivered'],
                'shipping_list' => [
                    [
                        'tracking_no' => $shippingData['tracking_no'],
                        'express_company' => $shippingData['express_company'],
                        'item_desc' => $shippingData['product_name'],
                        'contact' => [
                            'receiver_contact' => $shippingData['contact']['receiver_contact'] ?? ''
                        ]
                    ]
                ],
                'payer' => [
                    'openid' => $shippingData['openid']
                ],
                'upload_time' => $shippingData['upload_time'],
            ]
        ]);

        return $result;
    }


    /**
     * @param array $confirmData
     * @return void
     * @throws ApiException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function orderConfirmReceive(array $confirmData)
    {
        app(MiniWechatService::class)->getApplication()->getClient()->post('wxa/sec/order/upload_shipping_info', [
            'json' => [
                'transaction_id' => $confirmData['transaction_id'],
                'received_time' => $confirmData['received_time']
            ]
        ]);
    }

}