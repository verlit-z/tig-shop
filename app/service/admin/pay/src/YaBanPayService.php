<?php

namespace app\service\admin\pay\src;

use app\service\admin\pay\PaymentService;
use app\service\admin\pay\PayService;
use app\service\api\admin\pay\src\Exception;
use exceptions\ApiException;
use tig\Http;
use utils\Config;
use utils\Time;
use utils\Util;

class YaBanPayService extends PayService
{
    const JSAPI_PAY = 'wechat'; //jsapi支付
    const APP_PAY = 'app'; //app支付
    const NATIVE_PAY = 'pc'; //扫码支付
    const MINI_PROGRAM_PAY = 'miniProgram'; //小程序支付
    const HTML_PAY = 'h5'; //h5支付
    const API_URL = 'https://mapi.yabandpay.com/Payments';


    private string|null $payType = null;
    //收银员账号的UID
    protected string $uid = '';
    //签名密钥
    protected string $secret_key = '';
    //支付方式
    protected string $method = '';

    protected string $pay_platform = '';
    protected string $pay_method = '';
    protected string $sub_pay_method = '';
    protected string $currency = 'CNY';

    public function __construct()
    {
        $config = Config::getConfig();
        $this->uid = $config['yabandpayUid'];
        $this->secret_key = $config['yabandpaySecretKey'];
        $this->currency = $config['yabanpayCurrency'];
    }

    /**
     * 支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function pay(array $order): array
    {
        if (empty($order['pay_sn']) || empty($order['user_id']) || empty($order['order_amount'])) {
            throw new ApiException(Util::lang('缺少支付参数！'));
        }
        $this->pay_platform = $order['pay_code'];
        try {
            switch ($this->getPayType()) {
                case self::NATIVE_PAY:
                    return $this->NativePay($order);
                case self::MINI_PROGRAM_PAY:
                    return $this->MiniPay($order);
                case self::APP_PAY:
                    return $this->AppPay($order);
                case self::JSAPI_PAY:
                    return $this->JsApiPay($order);
                default:
                    return $this->HtmlPay($order);
            }
        } catch (Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 扫码支付--使用yabanpay下的二维码支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function NativePay(array $order): array
    {
        $this->method = 'v3.QRcodePayment';
        $this->pay_method = 'offline';
        $this->sub_pay_method = 'QRcode';

        return $this->getPayResult($order);
    }

    /**
     * APP支付--使用yabanpay下的app支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function AppPay(array $order): array
    {
        $this->pay_method = 'online';
        switch ($order['pay_code']) {
            case 'yabanpay_wechat':
                $this->method = 'v3.CreatePaymentsWechatAPPPay';
                $this->sub_pay_method = 'WeChat Pay';
                break;
            case 'yabanpay_alipay';
                $this->method = 'v3.CreatePaymentsAlipayAppPay';
                $this->sub_pay_method = 'Alipay';
                break;
        }

        return $this->getPayResult($order);
    }

    /**
     * 微信小程序支付--使用yabanpay下的小程序支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function MiniPay(array $order): array
    {
        $this->method = 'v3.CreatePaymentsWechatMiniPay';
        $this->pay_method = 'online';
        $this->sub_pay_method = 'WeChat Pay';

        return $this->getPayResult($order);
    }

    /**
     * h5支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function HtmlPay(array $order): array
    {
        $this->pay_method = 'online';
        switch ($order['pay_code']) {
            case 'yabanpay_wechat':
                $this->method = 'v3.CreatePaymentsWechatH5Pay';
                $this->sub_pay_method = 'WeChat Pay';
                break;
            case 'yabanpay_alipay';
                $this->method = 'v3.CreatePaymentsAlipayWap';
                $this->sub_pay_method = 'Alipay';
                break;
        }
        return $this->getPayResult($order);
    }

    /**
     * 微信内支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function JsApiPay(array $order): array
    {
        $this->pay_method = 'online';
        $this->method = 'v3.CreatePayments';
        $this->sub_pay_method = 'WeChat Pay';

        return $this->getPayResult($order);
    }

    /**
     * 获取支付参数
     * @param $order
     * @return array
     * @throws ApiException
     */
    public function getPayResult($order): array
    {
        try {
            $data = $this->getPayParams($order);
            $res = Http::post(self::API_URL, json_encode($data));
            $return = json_decode($res, true);
            if ($return['status']) {
                //处理返回数据
                if (isset($return['data']['url'])) $return['data']['code_url'] = $return['data']['url'];
                if (isset($return['data']['parameters']['pay_url'])) $return['data']['url'] = $return['data']['parameters']['pay_url'];
                return $return['data'];
            } else {
                throw new ApiException($return['message']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 退款
     * @param array $order
     * @return array|string[]
     * @throws ApiException
     */
    public function refund(array $order): array
    {
        try {
            $data = $this->getRefundParams($order['pay_sn'], $order['refund_sn'], $order['order_refund']);
            $res = Http::post(self::API_URL, json_encode($data));
            $return = json_decode($res, true);
            if (isset($return['status']) && $return['status']) {
                //提交成功
                return ['code' => 'SUCCESS', 'message' => '支付成功'];
            } else {
                throw new ApiException($return['message']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 支付回调
     * @return string[]
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function notify(): array
    {
        $message = request()->post();
        if (!isset($message['data'])) return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        $data = $message['data'];
        if ($data['state'] === 'paid') {
            //验签
            $ds = [];
            if (isset($data['3ds'])) {
                $ds = $data['3ds'];
                unset($data['3ds']);
            }
            $params = array_merge($data, $ds);
            $sign = self::getSign($params);
            if ($sign != $message['sign']) {
                return ['code' => 'FAIL', 'message' => Util::lang('验签失败')];
            }
            //支付成功
            $pay_sn = $data['order_id'];
            app(PaymentService::class)->paySuccess($pay_sn, $data['trade_id']);

            return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
        } else {
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        }
    }

    /**
     * 退款回调
     * @return string[]
     * @throws ApiException
     */
    public function refund_notify(): array
    {
        $message = request()->post();
        if (!isset($message['data'])) return ['code' => 'FAIL', 'message' => '失败'];
        $data = $message['data'];
        if ($data['state'] === 'refunded') {
            $sign = self::getSign($data);
            if ($sign != $message['sign']) {
                return ['code' => 'FAIL', 'message' => Util::lang('验签失败')];
            }
            if (isset($data['m_refund_id'])) {
                $refund_sn = $data['m_refund_id'];
                app(PaymentService::class)->refundSuccess($refund_sn);
                return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
            }
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        } else {
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        }
    }

    /**
     * 获取平台类型
     * @return string
     */
    public function getPayType(): string
    {
        if ($this->payType === null) {
            return Util::getClientType();
        } else {
            return $this->payType;
        }
    }

    /**
     * 获取签名
     * @param $params
     * @return string
     */
    public function getSign($params): string
    {
        $secret_key = $this->secret_key;
        ksort($params);
        $newArr = [];
        foreach ($params as $key => $item) {
            if (!empty($item)) { //剔除参数值为空的参数
                $newArr[] = $key . '=' . $item; // 整合新的参数数组
            }
        }
        $string = implode("&", $newArr); //使用 & 符号连接参数
        return hash_hmac('sha256', $string, $secret_key);
    }

    /**
     *获取支付参数
     * @param $order
     * @return array
     */
    public function getPayParams($order): array
    {
        $user = $this->uid;
        $method = $this->method;
        $time = Time::now();
        $data = [
            'pay_method' => $this->pay_method,
            'sub_pay_method' => $this->sub_pay_method,
            'order_id' => $order['pay_sn'],
            'amount' => $order['order_amount'],
            'currency' => $this->currency,
            'description' => '用户购买商品支付',
            'timeout' => '15',
            //'redirect_url' => $this->getReturnUrl($order['order_id']),
            'notify_url' => $this->getNotifyUrl('yabanpay'),
            'ip_address' => Util::getUserIp()
        ];
        $base = [
            'user' => $user,
            'method' => $method,
            'time' => $time,
        ];
        $params = array_merge($base, $data);
        $sign = self::getSign($params);
        $base['sign'] = $sign;
        $base['data'] = $data;

        return $base;
    }

    /**
     * 获取退款参数
     * @param string $trade_id
     * @param string $m_refund_id
     * @param int|float $refund_amount
     * @return array
     */
    public function getRefundParams(string $trade_id, string $m_refund_id, int|float $refund_amount): array
    {
        $user = $this->uid;
        $method = 'v3.CreateRefund';
        $time = Time::now();
        $data = [
            'trade_id' => $trade_id,
            'm_refund_id' => $m_refund_id,
            'refund_amount' => $refund_amount,
            'refund_currency' => $this->currency,
            'refund_description' => '用户发起退款',
            'notify_url' => $this->getRefundNotifyUrl('yabanpay')
        ];
        $base = [
            'user' => $user,
            'method' => $method,
            'time' => $time,
        ];
        $params = array_merge($base, $data);
        $sign = self::getSign($params);
        $base['sign'] = $sign;
        $base['data'] = $data;

        return $base;

    }

}