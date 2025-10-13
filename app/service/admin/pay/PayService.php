<?php

namespace app\service\admin\pay;

use utils\Config;

abstract class PayService
{
    /**
     * 下单
     * @return mixed
     */
    abstract public function pay(array $order): array;

    /**
     * 退款
     * @return mixed
     */
    abstract public function refund(array $order): array;

    /**
     * 回调处理
     * @return bool
     */
    abstract public function notify(): array;

    /**
     * 退款回调
     * @return array
     */
    abstract public function refund_notify(): array;

    /**
     * 获取支付回调
     * @return string
     */
    public function getNotifyUrl(string $pay_code = ''): string
    {
        $domain = Config::get('pcDomain');
        if (empty($domain)) $domain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $notify_url = $domain . '/api/order/pay/notify';
        if ($pay_code) $notify_url .= '?payCode=' . $pay_code;

        return $notify_url;
    }

    /**
     * 获取退款通知地址
     * @return string
     */
    public function getRefundNotifyUrl(string $pay_code = ''): string
    {
        $domain = Config::get('pcDomain');
        if (empty($domain)) $domain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $refund_url = $domain . '/api/order/pay/refundNotify';
        if ($pay_code) $refund_url .= '?pay_code=' . $pay_code;

        return $refund_url;
    }

    /**
     * 获取同步跳转地址
     * @param int $order_id
     * @return string
     */
    public function getReturnUrl(int $order_id = 0): string
    {
        $domain = Config::get('pcDomain');
        if (empty($domain)) $domain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        if ($order_id) {
            return $domain . '/member/order/info?id=' . $order_id;
        }
        return $domain . '/member/order/list';
    }
}
