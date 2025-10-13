<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 退款申请
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\finance;

use app\model\finance\RefundApply;
use app\model\finance\RefundLog;
use app\model\order\Aftersales;
use app\model\order\AftersalesItem;
use app\model\order\Order;
use app\model\user\User;
use app\service\admin\common\sms\SmsService;
use app\service\admin\order\AftersalesService;
use app\service\admin\order\OrderService;
use app\service\admin\pay\PayLogRefundService;
use app\service\admin\pay\PayLogService;
use app\service\admin\pay\PaymentService;
use app\service\admin\pay\src\AliPayService;
use app\service\admin\pay\src\PayPalService;
use app\service\admin\pay\src\WechatPayService;
use app\service\admin\pay\src\YaBanPayService;
use app\service\admin\product\ProductService;
use app\service\admin\product\ProductSkuService;
use app\service\admin\promotion\SeckillService;
use app\service\admin\user\UserRankService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Time;
use utils\Util;

/**
 * 退款申请服务类
 */
class RefundApplyService extends BaseService
{

    public function __construct()
    {
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter)->with(["aftersales"])->append(["refund_type_name", "refund_status_name"]);
        $result = $query->page($filter['page'], $filter['size'])->select();

        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = RefundApply::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->hasWhere('aftersales', function ($query) use ($filter) {
                $query->where('aftersales_sn', 'like', "%$filter[keyword]%");
            });
        }

        // 退款状态
        if (isset($filter['refund_status']) && $filter["refund_status"] != -1) {
            $query->where('refund_status', $filter["refund_status"]);
        }

        // 申请退款时间
        if (isset($filter['add_time']) && !empty($filter['add_time'])) {
            $filter['add_time'] = is_array($filter['add_time']) ? $filter['add_time'] : explode(',', $filter['add_time']);
            list($start_date, $end_date) = $filter['add_time'];
            $start_date = Time::toTime($start_date);
            $end_date = Time::toTime($end_date) + 86400;
            $pay_time = [$start_date, $end_date];
            $query->whereTime('add_time', 'between', $pay_time);
        }

        // 店铺检索
        if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
            $query->where('shop_id', $filter['shop_id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     * @param int $id
     * @return RefundApply
     * @throws ApiException
     */
    public function getDetail(int $id): RefundApply
    {
        $result = RefundApply::with(["aftersales" => ['orders'], 'order_info'])->append([
            "refund_type_name",
            "refund_status_name"
        ])->find($id);
        $order = Order::find($result['order_id']);
        if (!$result) {
            throw new ApiException(/** LANG */'退款申请不存在');
        }
        $price = 0;
        foreach ($result->items as $item) {
            // 售后申请数量
            $item->number = AftersalesItem::where(["order_item_id" => $item->item_id, "aftersale_id" => $result->aftersale_id])->value("number") ?? 0;
            // 退款商品总价格
            $price += $item->price * $item->number;
        }

        // 排除退款成功的支付金额
        $complete_order = RefundApply::where(["order_id" => $result->order_id])->whereIn('refund_status', [1, 2]);
        $complete_balance = $complete_order->sum('refund_balance');
        $complete_online_balance = $complete_order->sum('online_balance');
        $complete_offline_balance = $complete_order->sum('offline_balance');
        // 已完成的总金额
        $total_complete_amount = $complete_balance + $complete_online_balance + $complete_offline_balance;

        //查询订单是否处于未发货状态，是的话退款金额要加上运费
        if($order->shipping_status == Order::SHIPPING_PENDING) {
            $result->aftersales->refund_amount = $result->aftersales->refund_amount + $order->shipping_fee;
        }
        // 售后协商可退金额必填 refund_amount
        $refund_amount = $result->aftersales->refund_amount;
        // 真实的线上剩余可退金额
        $result->effective_online_balance = $order->online_paid_amount - $complete_online_balance;
        if ($refund_amount > 0) {
            $result->effective_online_balance = $result->effective_online_balance > $refund_amount ? $refund_amount : $result->effective_online_balance;
        }
        // 转换数据类型
        $result->effective_online_balance = Util::number_format_convert($result->effective_online_balance);
        $result->total_complete_amount = Util::number_format_convert($total_complete_amount);

        return $result;
    }

    /**
     * 执行退款申请更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function auditRefundApply(int $id, array $data): bool
    {
        $apply = $this->getDetail($id);
        // 可退款总金额
        $order_info = Order::find($apply->order_id);
        if($order_info->shipping_status == Order::SHIPPING_PENDING) {
            $apply->aftersales->refund_amount = $apply->aftersales->refund_amount + $order_info->shipping_fee;
        }
        $refund_amount = $apply->aftersales->refund_amount;
        //打款凭证
        $apply->payment_voucher = $data['payment_voucher'] ?? '';
        if($refund_amount <= 0) {
            throw new ApiException(/** LANG */'售后申请退款金额为0,无法退款');
        }
        if (!$apply) {
            throw new ApiException(/** LANG */'该申请不存在');
        }


        if ($apply->refund_status != RefundApply::REFUND_STATUS_WAIT) {
            throw new ApiException(/** LANG */'申请状态值错误');
        }

        // 线上金额限制，线下/余额只要不超过可退款总金额即可，最后三个值之和等于可退款总金额
        if ($data["online_balance"] > $apply->effective_online_balance) {
            throw new ApiException(/** LANG */'填写的线上金额不能超过可退的在线支付金额');
        }

        if ($data["refund_balance"] > $refund_amount) {
            throw new ApiException(/** LANG */'填写的余额不能超过可退款总金额');
        }

        if ($data['offline_balance'] > $refund_amount) {
            throw new ApiException(/** LANG */'填写的线下金额不能超过可退款总金额');
        }

        if ($data["refund_status"] == 1 && array_sum([$data["online_balance"], $data["offline_balance"], $data["refund_balance"]]) == 0) {
            throw new ApiException(/** LANG */'退款总金额不能为0');
        }

        if (array_sum([$data["online_balance"], $data["offline_balance"], $data["refund_balance"]]) != $refund_amount) {
          //  throw new ApiException(/** LANG */'填写的退款总金额要等于售后可退款金额:' . $refund_amount);
        }

        try {
            Db::startTrans();
            $online_balance = '0.00';
            $refund_balance = '0.00';
            $offline_balance = '0.00';
            if ($data["refund_status"] == 1) {
                if ($data["online_balance"] > 0) {
                    // 执行退款流程
                    $pay_params = [
                        "order_id" => $apply->order_id,
                        'refund_id' => $apply->refund_id,
                        "order_refund" => $data["online_balance"],
                        "paylog_desc" => $data["refund_note"],
                    ];
                    RefundLog::create(
                        [
                            "order_id" => $apply->order_id,
                            "refund_apply_id" => $apply->refund_id,
                            "refund_type" => 1,
                            "refund_amount" => $data["online_balance"],
                            "user_id" => $apply->user_id,
                        ]
                    );
                    if ($this->refundFlow($pay_params)) {
                        $apply->is_online = 1;
                        $apply->online_balance = $data["online_balance"];
                    }
                    $online_balance = bcadd($online_balance, $data["online_balance"], 2);
                }

                // 余额退款
                if ($data["refund_balance"] > 0) {
                    RefundLog::create(
                        [
                            "order_id" => $apply->order_id,
                            "refund_apply_id" => $apply->refund_id,
                            "refund_type" => 2,
                            "refund_amount" => $data["refund_balance"],
                            "user_id" => $apply->user_id,
                        ]
                    );
                    if (app(UserService::class)->incBalance($data["refund_balance"], $apply->user_id)) {
                        $apply->is_receive = 2;
                        $apply->refund_balance = $data["refund_balance"];
                    }
                    $refund_balance = bcadd($refund_balance, $data["refund_balance"], 2);
                }
                if ($data["offline_balance"] > 0) {
                    RefundLog::create(
                        [
                            "order_id" => $apply->order_id,
                            "refund_apply_id" => $apply->refund_id,
                            "refund_type" => 3,
                            "refund_amount" => $data["offline_balance"],
                            "user_id" => $apply->user_id,
                        ],
                    );
                    $apply->is_offline = 1;
                    $apply->offline_balance = $data["offline_balance"];
                    $offline_balance = bcadd($offline_balance, $data["offline_balance"], 2);
                }
            }

            //退回订单涉及到的库存
            $this->refundStock($apply->items);

            if ($apply->checkRefundSuccess()) {
                $apply->setRefundSuccess();
                $this->sendSms($order_info->user_id, $order_info->order_sn);
            }
            $result = $apply->save();

            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            throw new ApiException($exception->getMessage());
        }
        return $result;
    }

    /**
     * 发送退款成功短信
     * @param $mobile
     * @return false|void
     * @throws ApiException
     */
    public function sendSms($user_id, $order_sn)
    {
        $user = User::findOrEmpty($user_id);
        if(empty($user)) {
            return false;
        }
        if(empty($user['mobile'])) {
            return false;
        }

        app(SmsService::class)->sendSms($user['mobile'], 'refund_apply_success', [$order_sn]);

    }

    /**
     * @param $after_items
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundStock($after_items) : bool
    {
        foreach ($after_items as $item) {
            //增加库存
            if ($item->sku_id > 0) {
                app(ProductSkuService::class)->incStock($item->sku_id, $item->number, $item->shop_id);
            } else {
                app(ProductService::class)->incStock($item->product_id, $item->number, $item->shop_id);
            }
            //减少销量
            app(ProductService::class)->decSales($item->product_id, $item->number);
            //秒杀品减少销量
            app(SeckillService::class)->decSales($item->product_id, $item->sku_id, $item->number);
            //秒杀返回库存
            app(SeckillService::class)->incStock($item->product_id, $item->sku_id, $item->number);
        }
        return true;
    }

    /**
     * 线下确认已退款
     * @param $refund_id
     * @return bool
     * @throws ApiException
     */
    public function offlineAudit($refund_id): bool
    {
        $apply = $this->getDetail($refund_id);
        if (!$apply) {
            throw new ApiException(/** LANG */'退款信息不存在');
        }
        if (!$apply->canAuditOffline()) {
            throw new ApiException(/** LANG */'该状态下不能确认线下已退款');
        }
        $apply->setOfflineSuccess();
        if ($apply->checkRefundSuccess()) {
            $apply->setRefundSuccess();
            // 扣减成长值
            app(UserRankService::class)->reduceGrowth($refund_id);
        }
        $order_info = Order::find($apply->order_id);
        $this->sendSms($order_info->user_id, $order_info->order_sn);
        return $apply->save();
    }

    /**
     * 执行退款流程
     * @param $data
     * @return bool
     * @throws ApiException
     */
    public function refundFlow($data): bool
    {
        //获取订单信息
        $order = app(OrderService::class)->getDetail($data['order_id']);
        // 获取支付信息
        $pay_log = app(PayLogService::class)->getPayLogByPaySn($order['out_trade_no']);
        if (!$pay_log) {
            throw new ApiException(/** LANG */'支付信息不存在');
        }
        $refund_sn = app(PayLogRefundService::class)->createRefundSn();
        app(PayLogRefundService::class)->creatPayLogRefund($pay_log['paylog_id'], $order['order_id'], $data['order_refund'], $refund_sn, $pay_log['pay_code'], $data['paylog_desc'], request()->adminUid);
        $pay_params = [
            "pay_sn" => $pay_log['pay_sn'],
            "refund_sn" => $refund_sn,
            "order_refund" => $data["order_refund"],
            "order_amount" => $pay_log['order_amount'],
        ];
        //创建退款接口日志
        try {
            switch ($pay_log['pay_code']) {
                case 'wechat':
                    $res = app(WechatPayService::class)->refund($pay_params);
                    break;
                case 'alipay':
                    $res = app(AliPayService::class)->refund($pay_params);
                    break;
                case 'paypal':
                    $res = app(PayPalService::class)->refund($pay_params);
                    break;
                case 'yabanpay_wechat':
                case 'yabanpay_alipay':
                    $pay_params['pay_sn'] = $pay_log['transaction_id'];
                    $res = app(YaBanPayService::class)->refund($pay_params);
                    break;
                default:
                    return throw new ApiException(/** LANG */'该支付方式不存在');
            }

        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
        if (isset($res['code']) && $res['code'] == 'SUCCESS') {
            // 退款成功
            app(PaymentService::class)->refundSuccess($refund_sn);
        }
        return false;
    }

    /**
     * 设置线上退款到账通知到财务退款
     * @param int $paylog_refund_id
     * @return bool
     */
    public function onlineRefundSuccess(int $paylog_refund_id): bool
    {
        $refundApply = RefundApply::where('paylog_refund_id', $paylog_refund_id)->find();
        if (!$refundApply) {
            return true;
        }
        $refundApply->setOnlineSuccess();
        if ($refundApply->checkRefundSuccess()) {
            $refundApply->setRefundSuccess();
        }
        return $refundApply->save();
    }

    /**
     * 获取退款金额统计
     * @param array $data
     * @return mixed
     */
    public function getRefundTotal(array $data,int $shopId = 0): mixed
    {
        return $this->filterQuery([
                "shop_id" => $shopId,
                "refund_status" => RefundApply::REFUND_STATUS_PROCESSED,
                'add_time' => $data
            ])
            ->field("SUM(online_balance + offline_balance + refund_balance) AS refund_amount")
            ->findOrEmpty()->refund_amount ?? 0;
    }

    /**
     * 获取退款金额list
     * @param array $data
     * @return array
     */
    public function getRefundList(array $data,int $shopId = 0): array
    {
        $list = $this->filterQuery([
                'shop_id' => $shopId,
                'refund_status' => RefundApply::REFUND_STATUS_PROCESSED,
                'add_time' => $data
            ])
            ->field("SUM(online_balance + offline_balance + refund_balance) AS refund_amount,MIN(add_time) AS add_time")
            ->select()->toArray();

        foreach ($list as $key => $item) {
            if (empty($item['refund_amount'])) {
                unset($list[$key]);
            }
        }
        return $list;
    }

    /**
     * 获取退款件数统计
     * @param array $data
     * @return int
     */
    public function getRefundItemTotal(array $data,int $shopId = 0): int
    {
        $subQuery = app(AftersalesService::class)->filterQuery([
            'status' => Aftersales::STATUS_COMPLETE,
            'shop_id' => $shopId,
            'add_time' => $data,
        ])->field("aftersale_id")->buildSql();

        $result = AftersalesItem::whereExists(function ($query) use ($subQuery) {
                    $query->table($subQuery)->alias("sub")->whereRaw("sub.aftersale_id = aftersales_item.aftersale_id");
                })
                ->field("SUM(number) as total")
                ->findOrEmpty();
        return $result->total ?? 0;
    }

    /**
     * 申请退款
     * @param array $data
     * @return bool
     */
    public function applyRefund(array $data): bool
    {
        $apply_data = [
            "refund_type" => $data["refund_type"],
            "order_id" => $data["order_id"],
            "user_id" => $data["user_id"],
            "aftersale_id" => $data["aftersale_id"],
            'shop_id' => $data["shop_id"]
        ];
        $result = RefundApply::create($apply_data);
        return $result !== false;
    }
}
