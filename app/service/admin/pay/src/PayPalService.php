<?php

namespace app\service\admin\pay\src;

use app\service\admin\pay\PaymentService;
use app\service\admin\pay\PayService;
use app\service\admin\setting\RegionService;
use exceptions\ApiException;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthManager;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\MoneyBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PaymentSourceBuilder;
use PaypalServerSdkLib\Models\Builders\PaypalWalletBuilder;
use PaypalServerSdkLib\Models\Builders\PaypalWalletExperienceContextBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\RefundRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\OAuthToken;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use Psr\Log\LogLevel;
use utils\Config;
use utils\Util;

class PayPalService extends PayService
{
    protected string $client_id = '';
    //签名密钥
    protected string $secret = '';
    //支付方式
    protected string $currency = 'CNY';

    public function __construct()
    {
        $config = Config::getConfig();
        $this->client_id = $config['paypalClientId'];
        $this->secret = $config['paypalSecret'];
        $this->currency = $config['paypalCurrency'];
    }

    /**
     * 发起支付
     * @param array $order
     * @return string[]
     * @throws ApiException
     */
    public function pay(array $order): array
    {
        $collect = [
            'body' => OrderRequestBuilder::init(
                CheckoutPaymentIntent::CAPTURE,
                [
                    PurchaseUnitRequestBuilder::init(
                        AmountWithBreakdownBuilder::init($this->currency, $order['order_amount'])->build()
                    )->referenceId($order['pay_sn'])->build()
                ]
            )->paymentSource(
                PaymentSourceBuilder::init()
                    ->paypal(
                        PaypalWalletBuilder::init()
                            ->experienceContext(
                                PaypalWalletExperienceContextBuilder::init()
                                    //同步跳转地址
                                    ->returnUrl($this->getReturnUrl($order['order_id']))
                                    //取消跳转地址
                                    ->cancelUrl($this->getReturnUrl($order['order_id']))
                                    ->build()
                            )
                            ->build()
                    )
                    ->build()
            )
                ->build(),
            'prefer' => 'return=minimal'
        ];
        $ordersController = $this->getApplication()->getOrdersController();
        $res = $ordersController->ordersCreate($collect);
        $result = $res->getBody();
        $result = json_decode($result, true);
        if (!isset($result['status'])) {
            throw new ApiException(Util::lang('支付发起失败：' . $result['message']));
        }
        if ($result['status'] == 'CREATED' || $result['status'] == 'PAYER_ACTION_REQUIRED') {
            //订单创建成功
            $url = '';
            $link = $result['links'];
            foreach ($link as $value) {
                if ($value['rel'] == 'payer-action' || $value['rel'] == 'approve') {
                    $url = $value['href'];
                }
            }
            return ['url' => $url];
        } else {
            throw new ApiException(Util::lang('支付发起失败：' . $result['message'] ?? ''));
        }
    }

    /**
     * 退款
     * @param array $order
     * @return array
     */
    public function refund(array $order): array
    {
        $refundController = $this->getApplication()->getPaymentsController();
        $collect = [
            'body' => RefundRequestBuilder::init()
                ->amount(
                    MoneyBuilder::init($this->currency, $order['order_refund'])->build()
                )
                ->customId($order['pay_sn'])
                ->build(),
            'prefer' => 'return=minimal'
        ];
        $res = $refundController->capturesRefund($collect);
        $result = $res->getBody();
        $result = json_decode($result, true);
        if (!isset($result['status'])) {
            throw new ApiException(Util::lang('支付发起失败：' . $result['message']));
        }
        if ($result['status'] == 'COMPLETED') {
            return ['code' => 'SUCCESS', 'message' => '支付成功'];
        } else {
            return ['code' => 'FAIL', 'message' => $res['message'] ?? ''];
        }
    }

    /**
     * 支付回调
     * @return array
     * @throws ApiException
     */
    public function notify(): array
    {
        $message = request()->post();
        $pay_sn = $message['resource']['invoice_number'];
        $transaction_id = $message['resource']['parent_payment'];
        //检测订单是否支付
        $query_data = $this->queryOrderPay($pay_sn);
        if (isset($query_data['status']) && $query_data['status'] == 'COMPLETED') {
            app(PaymentService::class)->paySuccess($pay_sn,$transaction_id);
            return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
        }else{
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        }
    }

    public function refund_notify(): array
    {
        return [];
    }

    /**
     * 公共请求
     * @return object|\PaypalServerSdkLib\PaypalServerSdkClient
     */
    public function getApplication(): object
    {
        return PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init($this->client_id, $this->secret)
            )
            ->environment(Environment::SANDBOX)
            ->build();
    }

    /**
     * 查询订单
     * @param string $outTradeNo
     * @return mixed
     */
    public function queryOrderPay(string $outTradeNo)
    {
        $collect = [
            'id' => $outTradeNo,
            'prefer' => 'return=minimal'
        ];
        $ordersController = $this->getApplication()->getOrdersController();
        $res = $ordersController->ordersGet($collect);
        $result = $res->getBody();
        $result = json_decode($result, true);

        return $result;
    }
}