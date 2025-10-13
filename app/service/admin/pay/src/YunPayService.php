<?php

namespace app\service\admin\pay\src;

use app\service\admin\oauth\UserAuthorizeService;
use app\service\admin\pay\PaymentService;
use app\service\admin\pay\PayService;
use \Exception;
use exceptions\ApiException;
use tig\Http;
use utils\Config;
use utils\Time;
use utils\Util;

class YunPayService extends PayService
{
    const JSAPI_PAY = 'wechat'; //jsapi支付
    const APP_PAY = 'app'; //app支付
    const NATIVE_PAY = 'pc'; //扫码支付
    const MINI_PROGRAM_PAY = 'miniProgram'; //小程序支付
    const HTML_PAY = 'h5'; //h5支付
    const API_URL = 'http://h5.yuntop.shop';


    private string|null $payType = null;
    //收银员账号的UID
    protected string $uid = '';
    //app id
    protected string $appid = '';
    //签名密钥
    protected string $secret_key = '';
    //支付方式
    protected string $method = '/Pay';

    protected string $pay_platform = '';
    protected string $pay_method = '';
    protected string $sub_pay_method = '';
    protected string $currency = 'CNY';

    protected $fxsubtype = 0;

    public function __construct()
    {
        $config = Config::getConfig();
        $this->uid = $config['yunpayUid'];
        $this->secret_key = $config['yunpaySecretKey'];
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
                    $this->fxsubtype = 1;
                    $this->appid = Config::get('wechatMiniProgramAppId');
                    return $this->MiniPay($order);

                case self::APP_PAY:
                    $this->appid = Config::get('wechatPayAppId');
                    return $this->AppPay($order);

                case self::JSAPI_PAY:
                    $this->fxsubtype = 2;
                    $this->appid = Config::get('wechatAppId');
                    $order['openid'] = app(UserAuthorizeService::class)->getUserAuthorizeOpenId($order['user_id'], 1);
                    return $this->JsApiPay($order);

                default:
                    return $this->HtmlPay($order);

            }
        } catch (Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function NativePay(array $order): array
    {
        return $this->AppPay($order);
    }

    /**
     * APP支付--使用yunpay下的
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function AppPay(array $order): array
    {
        $this->pay_method = 'online';
        switch ($order['pay_code']) {
            case 'yunpay_wechat':
                $this->sub_pay_method = 'weixin';
                break;
            case 'yunpay_alipay';
                $this->sub_pay_method = 'alipay';
                break;
            case 'yunpay_yunshanfu';
                $this->sub_pay_method = 'yunshanfu';
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
        return $this->AppPay($order);
    }

    /**
     * h5支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function HtmlPay(array $order): array
    {
        return $this->AppPay($order);
    }

    /**
     * 微信内支付
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function JsApiPay(array $order): array
    {
        return $this->AppPay($order);
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
            $res = Http::post(self::API_URL . '/Pay', $data);
            $return = json_decode($res, true);
            if ($return['status']) {
                //处理返回数据
                if (isset($return['payinfo'])) {
                    $return = json_decode($return['payinfo'], true);
                } elseif (isset($return['payurl'])) {
                    $return = ['code_url' => $return['payurl']];
                }
                return $return;
            } else {
                throw new ApiException($return['error']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    public function getReturnUrl(int $order_id = 0): string
    {
        $domain = Config::get('pcDomain');
        if (empty($domain)) {
            $domain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }
        if ($order_id) {
            return $domain . '/member/order/list';
        }
        return $domain . '/member/order/list';
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
            $res = Http::post(self::API_URL, $data);
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
        $data = request()->post();
        if (!isset($data['fxstatus'])) {
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
        }
        if ($data['fxstatus'] == '1') {
            //验签
            $sign = md5($data['fxstatus'] . $data['fxid'] . $data['fxordernum'] . $this->secret_key);
            if ($sign != $data['fxsign']) {
                return ['code' => 'FAIL', 'message' => Util::lang('验签失败')];
            }
            //支付成功
            $pay_sn = $data['fxordernum'];
            app(PaymentService::class)->paySuccess($pay_sn, $data['fxbankordernum']);

            return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
        } elseif ($data['fxstatus'] == '4') {
            //验签
            $sign = md5($data['fxstatus'] . $data['fxid'] . $data['fxordernum'] . $this->secret_key);
            if ($sign != $data['fxsign']) {
                return ['code' => 'FAIL', 'message' => Util::lang('验签失败')];
            }
            if (isset($data['fxordernum'])) {
                $refund_sn = $data['fxordernum'];
                app(PaymentService::class)->refundSuccess($refund_sn);
                return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
            }
            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
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
//        $data = request()->post();
//        if (!isset($data['fxstatus'])) return ['code' => 'FAIL', 'message' => Util::lang('失败')];
//        if ($data['fxstatus'] === '1') {
//            //验签
//            $sign = md5($data['fxstatus'].$data['fxid'].$data['fxordernum'].$this->secret_key);
//            if ($sign != $data['fxsign']) {
//                return ['code' => 'FAIL', 'message' => Util::lang('验签失败')];
//            }
//            if (isset($data['fxordernum'])) {
//                $refund_sn = $data['fxordernum'];
//                app(PaymentService::class)->refundSuccess($refund_sn);
//                return ['code' => 'SUCCESS', 'message' => Util::lang('支付成功')];
//            }
//            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
//        } else {
//            return ['code' => 'FAIL', 'message' => Util::lang('失败')];
//        }
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

        return md5($this->uid . $params['fxordernum'] . $params['fxfee'] . $params['fxnotifyurl'] . $secret_key);
    }

    /**
     *获取支付参数
     * @param $order
     * @return array
     */
    public function getPayParams($order): array
    {
        $user = $this->uid;
        $data = [
            'fxpay' => $this->sub_pay_method,
            'fxordernum' => $order['pay_sn'],
            'fxfee' => $order['order_amount'],
            'fxdesc' => '用户购买商品支付',
            'fxbackurl' => $this->getReturnUrl($order['order_id']),
            'fxnotifyurl' => $this->getNotifyUrl('yunpay'),
            "fxnotifystyle" => 2,
            "fxrisk" => json_encode([
                "fxmemberid" => $order['user_id'],
                "fxip" => Util::getUserIp()
            ]),
        ];
        if (!empty($this->appid)) {
            $openid = $order['openid'];
            $data['fxextra'] = json_encode([
                'fxsubtype' => $this->fxsubtype,
                'fxappid' => $this->appid,
                'fxopenid' => $openid
            ]);
        }

        $data['fxid'] = $user;
        $sign = self::getSign($data);
        $data['fxsign'] = $sign;
        return $data;
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
        $data = [
            'fxordernum' => $trade_id,
            'fxaction' => 'orderrefund',
        ];
        $base = [
            'fxid' => $user,
        ];
        $params = array_merge($base, $data);
        $base['fxsign'] = md5($this->uid . $params['fxordernum'] . $params['fxaction'] . $this->secret_key);
        $base['data'] = $data;

        return $base;

    }

}