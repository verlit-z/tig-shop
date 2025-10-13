<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 充值
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\finance\UserRechargeOrderService;
use app\service\admin\oauth\WechatOAuthService;
use app\service\admin\pay\PayLogService;
use app\service\admin\pay\PaymentService;
use app\service\admin\pay\src\AliPayService;
use app\service\admin\pay\src\PayPalService;
use app\service\admin\pay\src\WechatPayService;
use app\service\admin\pay\src\YaBanPayService;
use app\service\admin\pay\src\YunPayService;
use exceptions\ApiException;
use think\App;
use think\Response;
use think\response\Json;
use utils\Util;

/**
 * 会员中心充值
 */
class RechargeOrder extends IndexBaseController
{
    protected UserRechargeOrderService $userRechargeOrderService;

    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app, UserRechargeOrderService $userRechargeOrderService)
    {
        parent::__construct($app);
        $this->userRechargeOrderService = $userRechargeOrderService;
    }

    /**
     * 充值记录列表
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
        ], 'get');
        $filter["size"] = 15;
        $filter["sort_field"] = "add_time";
        $filterResult = $this->userRechargeOrderService->getAccountDetails($filter, request()->userId);

        return $this->success([
            'records' => $filterResult["list"],
            'total' => $filterResult["count"],
        ]);
    }

    /**
     * 充值申请
     * @return Response
     */
    public function update(): Response
    {
        $id = $this->request->all('id/d', 0);
        $amount = $this->request->all('amount/f', 0);
        $order_id = $this->userRechargeOrderService->rechargeOperation($id, $amount, request()->userId);
        return $order_id ? $this->success(['order_id' => $order_id]) : $this->error(/** LANG */Util::lang("充值申请失败"));
    }

    /**
     * 充值金额列表
     * @return Response
     */
    public function setting(): Response
    {
        $filter = $this->request->only([
            'sort_field' => 'sort_order',
            'sort_order' => 'asc',
        ], 'get');

        $filterResult = $this->userRechargeOrderService->getSettingList($filter);

        return $this->success($filterResult["list"]);
    }

    /**
     * 充值支付列表
     * @return Response
     */
    public function paymentList(): Response
    {
        $payment_list = app(PaymentService::class)->getAvailablePayment('recharge');
        return $this->success($payment_list);
    }

    /**
     * 充值支付
     * @return Json
     * @throws \exceptions\ApiException
     */
    public function pay(): Response
    {
        $order_id = $this->request->all('order_id/d', 0);
        $order = app(UserRechargeOrderService::class)->getDetail($order_id);
        //已支付订单跳转
        if ($order['status'] == 1){
            return $this->error(Util::lang('订单已支付'));
        }
        $payment_list = app(PaymentService::class)->getAvailablePayment();
        //过滤线下支付
        $payment_list = array_filter($payment_list, function ($method) {
            return $method !== 'offline';
        });
        return $this->success([
            'order' => $order,
            'payment_list' => $payment_list,
        ]);
    }

    /**
     * 充值支付
     * @return Json
     * @throws ApiException
     */
    public function create(): Response
    {
        $order_id = $this->request->all('id/d', 0);
        $pay_type = $this->request->all('type', '');
        if (empty($pay_type)) {
            return $this->error(Util::lang('未选择支付方式'));
        }
        $code = $this->request->all('code', '');
        $openid = '';
        if (!empty($code)) {
            $openid = app(WechatOAuthService::class)->getMiniOpenid($code);
        }
        $order = app(UserRechargeOrderService::class)->getDetail($order_id)->toArray();
        $order['order_type'] = 1;
        $order['pay_code'] = $pay_type;
        $order['total_amount'] = $order['amount'];
        $pay_params = app(PayLogService::class)->createRechargePayLogParams($order);
        $pay_params['paylog_id'] = app(PayLogService::class)->createRechargePayLog($pay_params);
        $pay_params['user_id'] = request()->userId;
        $pay_params['openid'] = $openid;
        try {
            switch ($pay_type) {
                case 'wechat':
                    $res = app(WechatPayService::class)->pay($pay_params);
                    break;
                case 'alipay':
                    $res = app(AliPayService::class)->pay($pay_params);
                    break;
                case 'paypal':
                    $res = app(PayPalService::class)->pay($pay_params);
                    break;
                case 'yabanpay_wechat':
                case 'yabanpay_alipay':
                    $res = app(YaBanPayService::class)->pay($pay_params);
                    break;
                case 'yunpay_wechat':
                case 'yunpay_alipay':
                case 'yunpay_yunshanfu':
                    $res = app(YunPayService::class)->pay($pay_params);
                    break;
                default:
                    return $this->error(Util::lang('未选择支付方式'));
            }
        } catch (\Exception $exception) {
            throw new ApiException(Util::lang($exception->getMessage()));
        }

        if (isset($res['code']) && $res['code'] == '1001') {

            return $this->error(Util::lang($res['msg']));
        }
        return $this->success([
            'order_id' => $pay_params['order_id'],
            'order_amount' => $pay_params['order_amount'],
            'pay_info' => $res,
        ]);
    }

    /**
     * 获取充值支付状态
     * @return Response
     * @throws ApiException
     */
    public function checkStatus(): Response
    {
        $order_id = $this->request->all('id/d', 0);
        if (empty($order_id)) {
            return $this->error(Util::lang('参数缺失'));
        }
        $order = app(UserRechargeOrderService::class)->getDetail($order_id);
        return $this->success($order['status']);
    }
}
