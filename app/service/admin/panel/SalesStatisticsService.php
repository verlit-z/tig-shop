<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 面板销售统计服务
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\panel;

use app\model\finance\UserRechargeOrder;
use app\model\order\Aftersales;
use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\user\Feedback;
use app\model\user\User;
use app\model\vendor\Vendor;
use app\model\vendor\VendorProduct;
use app\model\vendor\VendorProductSku;
use app\model\vendor\VendorShopBind;
use app\service\admin\finance\RefundApplyService;
use app\service\admin\finance\UserRechargeOrderService;
use app\service\admin\order\OrderService;
use app\service\admin\product\ProductService;
use app\service\admin\sys\StatisticsService;
use app\service\admin\user\FeedbackService;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;

/**
 * @param $data
 * @return array
 * 面板信息类
 */
class SalesStatisticsService extends BaseService
{


    /**
     * 面板控制台 - 控制台数据
     * @return array
     */
    public function getConsoleData(int $shopId): array
    {
        // 待付款订单
        $awaitPayTotal = app(OrderService::class)->getFilterCount([
            'shop_id' => $shopId,
            'order_status' => Order::ORDER_PENDING
        ]);
        // 待发货的订单
        $await_ship = app(OrderService::class)->getFilterCount([
            'shop_id' => $shopId,
            'order_status' => Order::ORDER_CONFIRMED
        ]);
        // 待售后的订单
        $await_after_sale = app(OrderService::class)->getFilterCount([
            'shop_id' => $shopId,
            'order_status' => [Order::ORDER_CONFIRMED,Order::ORDER_PROCESSING,Order::ORDER_COMPLETED]
        ]);
        // 待回复的订单留言
        $await_feedback = app(FeedbackService::class)->getFilterCount([
            'shop_id' => $shopId,
            'status' => 0,
            'parent_id' => 0,
            'type' => [Feedback::TYPE_ORDER_PROBLEM,Feedback::TYPE_ORDER_ASK]
        ]);
        $result = [
            'await_pay' => $awaitPayTotal,
            'await_ship' => $await_ship,
            'await_after_sale' => $await_after_sale,
            'await_comment' => $await_feedback,
        ];
        return $result;
    }

    /**
     * 面板控制台 - 实时数据
     * @return array
     */
    public function getRealTimeData(int $shopId): array
    {
        // 当天时间段
        $today = Time::getCurrentDatetime("Y-m-d");
        $start_end_time = [$today, $today];
        // 获取环比时间区间
        $prev_date = app(StatisticsUserService::class)->getPrevDate($start_end_time,3);
        // 支付金额
        $today_order_amount = app(OrderService::class)->filterQuery([
            'shop_id' => $shopId,
            'pay_time' => $start_end_time,
            'pay_status' => Order::PAYMENT_PAID
        ])->sum('total_amount');

        $yesterday_order_amount = app(OrderService::class)->filterQuery([
            'shop_id' => $shopId,
            'pay_time' => $prev_date,
            'pay_status' => Order::PAYMENT_PAID
        ])->sum('total_amount');

        $order_amount_growth_rate = app(StatisticsUserService::class)->getGrowthRate($today_order_amount, $yesterday_order_amount);

        // 访客数
        $today_visit_num = app(StatisticsService::class)->getVisitNum($start_end_time, 0, 0, $shopId);
        $yesterday_visit_num = app(StatisticsService::class)->getVisitNum($prev_date, 0, 0, $shopId);
        $visit_growth_rate = app(StatisticsUserService::class)->getGrowthRate($today_visit_num, $yesterday_visit_num);

        //支付买家数
        $today_buyer_num = app(OrderService::class)->filterQuery([
            'pay_time' => $start_end_time,
            'pay_status' => Order::PAYMENT_PAID,
            'shop_id' => $shopId
        ])->group("user_id")->count();

        $yesterday_buyer_num = app(OrderService::class)->filterQuery([
            'pay_time' => $prev_date,
            'pay_status' => Order::PAYMENT_PAID,
            'shop_id' => $shopId
        ])->group("user_id")->count();

        $buyer_growth_rate = app(StatisticsUserService::class)->getGrowthRate($today_buyer_num, $yesterday_buyer_num);

        // 浏览量
        $today_view_num = app(StatisticsService::class)->getVisitNum($start_end_time, 1, 0, $shopId);
        $yesterday_view_num = app(StatisticsService::class)->getVisitNum($prev_date, 1, 0, $shopId);
        $view_growth_rate = app(StatisticsUserService::class)->getGrowthRate($today_view_num, $yesterday_view_num);

        // 支付订单数
        $today_order_num = app(OrderService::class)->filterQuery([
            'pay_time' => $start_end_time,
            'pay_status' => Order::PAYMENT_PAID,
            'shop_id' => $shopId
        ])->count();

        $yesterday_order_num = app(OrderService::class)->filterQuery([
            'pay_time' => $prev_date,
            'pay_status' => Order::PAYMENT_PAID,
            'shop_id' => $shopId
        ])->count();
        $order_growth_rate = app(StatisticsUserService::class)->getGrowthRate($today_order_num, $yesterday_order_num);

        $result = [
            "today_order_amount" => $today_order_amount,
            "yesterday_order_amount" => $yesterday_order_amount,
            "order_amount_growth_rate" => $order_amount_growth_rate,
            "today_visit_num" => $today_visit_num,
            "yesterday_visit_num" => $yesterday_visit_num,
            "visit_growth_rate" => $visit_growth_rate,
            "today_buyer_num" => $today_buyer_num,
            "yesterday_buyer_num" => $yesterday_buyer_num,
            "buyer_growth_rate" => $buyer_growth_rate,
            "today_view_num" => $today_view_num,
            "yesterday_view_num" => $yesterday_view_num,
            "view_growth_rate" => $view_growth_rate,
            "today_order_num" => $today_order_num,
            "yesterday_order_num" => $yesterday_order_num,
            "order_growth_rate" => $order_growth_rate,
        ];
        return $result;
    }

    /**
     * 面板控制台 - 统计图表
     * @return array
     */
    public function getPanelStatisticalData(int $shopId = 0): array
    {
        // 默认为一个月的数据
        $today = Time::getCurrentDatetime("Y-m-d");
        $month_day = Time::format(Time::monthAgo(1), "Y-m-d");
        $start_end_time = app(StatisticsUserService::class)->getDateRange(0, [$month_day, $today]);
        // 访问统计
        $access_data = app(StatisticsService::class)->getVisitList($start_end_time, 1, 0, $shopId);

        // 订单统计 -- 订单数量/ 订单金额
        $order_data = app(OrderService::class)->filterQuery([
                'pay_time' => $start_end_time,
                'pay_status' => Order::PAYMENT_PAID,
                'shop_id' => $shopId
            ])
            ->field("DATE_FORMAT(FROM_UNIXTIME(pay_time), '%Y-%m-%d') AS period")
            ->field("COUNT(*) AS order_count,SUM(total_amount) AS order_amount")
            ->group("period")
            ->select()->toArray();

        // 横轴
        $horizontal_axis = app(StatisticsUserService::class)->getHorizontalAxis(0, $month_day, $today);
        // 访问统计 -- 纵轴
        $longitudinal_axis_access = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $access_data, 0, 2);
        // 订单统计 -- 订单数量
        $longitudinal_axis_order_num = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $order_data, 0, 3);
        // 订单金额
        $longitudinal_axis_order_amount = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $order_data, 0, 7);
        $result = [
            "horizontal_axis" => $horizontal_axis,
            "longitudinal_axis_access" => $longitudinal_axis_access,
            "longitudinal_axis_order_num" => $longitudinal_axis_order_num,
            "longitudinal_axis_order_amount" => $longitudinal_axis_order_amount,
        ];
        return $result;
    }

    /**
     * 销售统计 -- 销售统计展示数据
     * @param array $filter
     * @return array
     * @throws ApiException
     */
    public function getSalesData(array $filter): array
    {
        if (empty($filter["start_end_time"])) {
            throw new ApiException('请选择日期');
        }
        $start_end_time = app(StatisticsUserService::class)->getDateRange($filter["date_type"], $filter["start_end_time"]);
        // 获取环比时间区间
        $prev_date = app(StatisticsUserService::class)->getPrevDate($start_end_time,$filter['date_type']);

        // 商品支付金额
        $product_payment = app(OrderService::class)->getPayMoneyTotal($start_end_time,$filter['shop_id']);
        $prev_product_payment = app(OrderService::class)->getPayMoneyTotal($prev_date,$filter['shop_id']);
        $product_payment_growth_rate = app(StatisticsUserService::class)->getGrowthRate($product_payment, $prev_product_payment);

        // 商品退款金额
        $product_refund = app(RefundApplyService::class)->getRefundTotal($start_end_time,$filter['shop_id']);
        $prev_product_refund = app(RefundApplyService::class)->getRefundTotal($prev_date,$filter['shop_id']);
        $product_refund_growth_rate = app(StatisticsUserService::class)->getGrowthRate($product_refund, $prev_product_refund);

        // 充值金额 -- 店铺后台排除充值金额显示
        $recharge_amount = app(UserRechargeOrderService::class)->filterQuery([
            'pay_time' => $start_end_time,
            'status' => UserRechargeOrder::STATUS_SUCCESS
        ])->sum('amount');
        $recharge_amount = $filter['shop_id'] > 0 ? 0 : $recharge_amount;

        $prev_recharge_amount = app(UserRechargeOrderService::class)->filterQuery([
            'pay_time' => $prev_date,
            'status' => UserRechargeOrder::STATUS_SUCCESS
        ])->sum('amount');
        $recharge_amount_growth_rate = app(StatisticsUserService::class)->getGrowthRate($recharge_amount, $prev_recharge_amount);
        $recharge_amount_growth_rate = $filter['shop_id'] > 0 ? 0 : $recharge_amount_growth_rate;

        // 营业额
        $turnover = bcadd($product_payment, $recharge_amount, 2);
        $prev_turnover = $prev_product_payment + $prev_recharge_amount;
        $turnover_growth_rate = app(StatisticsUserService::class)->getGrowthRate($turnover, $prev_turnover);

        // 余额支付金额
        $balance_payment = app(OrderService::class)->getPayBalanceTotal($start_end_time,$filter['shop_id']);
        $prev_balance_payment = app(OrderService::class)->getPayBalanceTotal($prev_date,$filter['shop_id']);
        $balance_payment_growth_rate = app(StatisticsUserService::class)->getGrowthRate($balance_payment, $prev_balance_payment);

        $result["sales_data"] = [
            "product_payment" => $product_payment,
            "product_payment_growth_rate" => $product_payment_growth_rate,
            "product_refund" => $product_refund,
            "product_refund_growth_rate" => $product_refund_growth_rate,
            "turnover" => $turnover,
            "turnover_growth_rate" => $turnover_growth_rate,
            "recharge_amount" => $recharge_amount,
            "recharge_amount_growth_rate" => $recharge_amount_growth_rate,
            "balance_payment" => $balance_payment,
            "balance_payment_growth_rate" => $balance_payment_growth_rate,
        ];

        // 获取统计图表数据
        $result["sales_statistics_data"] = $this->getSalesStatisticsData($filter["date_type"], $start_end_time, $filter["statistic_type"],$filter['shop_id']);
        // 导出
        if ($filter["is_export"]) {
            // 导出
            if ($filter["statistic_type"]) {
                app(StatisticsUserService::class)->executeExport($result["sales_statistics_data"], $filter["date_type"], 4);
            } else {
                app(StatisticsUserService::class)->executeExport($result["sales_statistics_data"], $filter["date_type"], 5);
            }
        }
        return $result;
    }

    /**
     * 销售统计 -- 统计图表数据
     * @param int $date_type
     * @param array $start_end_time
     * @param int $statistic_type
     * @return array
     */
    public function getSalesStatisticsData(int $date_type, array $start_end_time, int $statistic_type,int $shopId = 0): array
    {
        list($start_date, $end_date) = $start_end_time;
        // 横轴
        $horizontal_axis = app(StatisticsUserService::class)->getHorizontalAxis($date_type, $start_date, $end_date);
        $order_statistics_list = app(OrderService::class)->getPayMoneyList($start_end_time,$shopId);
        if ($statistic_type) {
            // 订单金额统计
            $longitudinal_axis = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $order_statistics_list, $date_type, 4);
        } else {
            // 订单数统计
            $longitudinal_axis = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $order_statistics_list, $date_type, 5);
        }
        $result = [
            "horizontal_axis" => $horizontal_axis,
            "longitudinal_axis" => $longitudinal_axis,
        ];
        return $result;
    }

    /**
     * 销售明细
     * @param array $filter
     * @return array
     * @throws ApiException
     */
    public function getSaleDetail(array $filter): array
    {
        if (empty($filter["start_time"]) || empty($filter["end_time"])) {
            throw new ApiException('请选择日期');
        }
        $start_end_time = [$filter["start_time"], $filter["end_time"]];
        // 获取环比时间区间
        $prev_date = app(StatisticsUserService::class)->getPrevDate($start_end_time,4);

        // 商品浏览量
		$product_view = app(StatisticsService::class)->getVisitNumByProduct($start_end_time, 1, 1,$filter['shop_id']);
        $prev_product_view = app(StatisticsService::class)->getVisitNumByProduct($prev_date, 1, 1,$filter['shop_id']);
        $product_view_growth_rate = app(StatisticsUserService::class)->getGrowthRate($product_view, $prev_product_view);

        // 商品访客数
        $product_visitor = app(StatisticsService::class)->getVisitNumByProduct($start_end_time, 0, 1,$filter['shop_id']);
        $prev_product_visitor = app(StatisticsService::class)->getVisitNumByProduct($prev_date, 0, 1,$filter['shop_id']);
        $product_visitor_growth_rate = app(StatisticsUserService::class)->getGrowthRate($product_visitor, $prev_product_visitor);

        // 下单件数
        $order_num = app(OrderService::class)->getOrderTotal($start_end_time,$filter['shop_id']);
        $prev_order_num = app(OrderService::class)->getOrderTotal($prev_date,$filter['shop_id']);
        $order_num_growth_rate = app(StatisticsUserService::class)->getGrowthRate($order_num, $prev_order_num);

        // 支付金额
        $payment_amount = app(OrderService::class)->getPayMoneyTotal($start_end_time,$filter['shop_id']);
        $prev_payment_amount = app(OrderService::class)->getPayMoneyTotal($prev_date,$filter['shop_id']);
        $payment_amount_growth_rate = app(StatisticsUserService::class)->getGrowthRate($payment_amount, $prev_payment_amount);

        // 退款金额
        $refund_amount = app(RefundApplyService::class)->getRefundTotal($start_end_time,$filter['shop_id']);
        $prev_refund_amount = app(RefundApplyService::class)->getRefundTotal($prev_date,$filter['shop_id']);
        $refund_amount_growth_rate = app(StatisticsUserService::class)->getGrowthRate($refund_amount, $prev_refund_amount);

        // 退款件数
        $refund_quantity = app(RefundApplyService::class)->getRefundItemTotal($start_end_time,$filter['shop_id']);
        $prev_refund_quantity = app(RefundApplyService::class)->getRefundItemTotal($prev_date,$filter['shop_id']);
        $refund_quantity_growth_rate = app(StatisticsUserService::class)->getGrowthRate($refund_quantity, $prev_refund_quantity);

        $result["sales_data"] = [
            "product_view" => $product_view,
            "product_view_growth_rate" => $product_view_growth_rate,
            "product_visitor" => $product_visitor,
            "product_visitor_growth_rate" => $product_visitor_growth_rate,
            "order_num" => $order_num,
            "order_num_growth_rate" => $order_num_growth_rate,
            "payment_amount" => $payment_amount,
            "payment_amount_growth_rate" => $payment_amount_growth_rate,
            "refund_amount" => $refund_amount,
            "refund_amount_growth_rate" => $refund_amount_growth_rate,
            "refund_quantity" => $refund_quantity,
            "refund_quantity_growth_rate" => $refund_quantity_growth_rate,
        ];

        $result["sales_statistics_data"] = $this->getSalesStatisticsDetail($start_end_time,$filter['shop_id']);
        return $result;
    }

    /**
     * 销售明细 -- 图表
     * @param array $start_end_time
     * @return array
     */
    public function getSalesStatisticsDetail(array $start_end_time,int $shopId = 0): array
    {
        list($start_date, $end_date) = $start_end_time;
        // 横轴
        $horizontal_axis = app(StatisticsUserService::class)->getHorizontalAxis(0, $start_date, $end_date);
        // 支付金额
        $payment_amount_list = app(OrderService::class)->getPayMoneyList($start_end_time,$shopId);

        $longitudinal_axis_payment_amount = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $payment_amount_list, 0, 4);

        // 退款金额
        $refund_amount_list = app(RefundApplyService::class)->getRefundList($start_end_time,$shopId);
        $longitudinal_axis_refund_amount = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $refund_amount_list, 0, 6);

        // 商品浏览量
        $product_view_list = app(StatisticsService::class)->getVisitList($start_end_time, 1, 1,$shopId);
        $longitudinal_axis_product_view = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $product_view_list, 0, 2);

        // 商品访客量
        $product_visitor_list = app(StatisticsService::class)->getVisitList($start_end_time, 0, 1,$shopId);
        $longitudinal_axis_product_visitor = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $product_visitor_list, 0, 2);

        $result = [
            "horizontal_axis" => $horizontal_axis,
            "longitudinal_axis_payment_amount" => $longitudinal_axis_payment_amount,
            "longitudinal_axis_refund_amount" => $longitudinal_axis_refund_amount,
            "longitudinal_axis_product_view" => $longitudinal_axis_product_view,
            "longitudinal_axis_product_visitor" => $longitudinal_axis_product_visitor,
        ];
        return $result;
    }

    /**
     * 销售商品明细
     * @param array $filter
     * @return array
     */
    public function getSaleProductDetail(array $filter): array
    {
        $start_end_time = [];
        if (!empty($filter["start_time"]) && !empty($filter["end_time"])) {
            $start_end_time = [$filter["start_time"], $filter["end_time"]];
        }

        $query = OrderItem::with(["orders"])
            ->hasWhere('orders',function ($query) use ($start_end_time) {
                $query->where("is_del", 0)
                    ->whereIn('order_status', [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED])
                    ->addTime($start_end_time);
            })
            ->visible(['orders' => ['order_sn', 'add_time']])
            ->field("(quantity * price) AS subtotal")
            ->where(function ($query) use ($filter) {
                if(!empty($filter["keyword"])){
                    $query->where("product_name|product_sn", "like", "%{$filter["keyword"]}%");
                }
                if(isset($filter['shop_id']) && $filter['shop_id'] > -1){
                    $query->where("OrderItem.shop_id", $filter['shop_id']);
                }
            });

        $count = $query->count();
        $total_list = $query->select()->toArray();
        $list = $query->page($filter["page"], $filter["size"])->order($filter["sort_field"], $filter["sort_order"])->select()->toArray();
        $result = [
            "count" => $count,
            "list" => $list,
        ];

        if ($filter["is_export"]) {
            // 导出
            $data = [];
            foreach ($total_list as $item) {
                if (!empty($item['sku_data'])) {
                    $sku_data = implode('|', array_map(function ($data) {
                        return $data['name'] . ':' . $data['value'];
                    }, $item['sku_data']));
                }
                $data[] = [
                    "product_name" => $item["product_name"],
                    "product_sn" => $item["product_sn"],
                    "sku_data" => $sku_data ?? '',
                    "order_sn" => $item["orders"]["order_sn"],
                    "quantity" => $item["quantity"],
                    "price" => $item["price"],
                    "subtotal" => $item["subtotal"],
                    "add_time" => $item["orders"]["add_time"],
                ];
            }
            app(StatisticsUserService::class)->executeExport($data, 0, 6);
        }
        return $result;
    }

    /**
     * 销售指标
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getSaleIndicators(int $shopId = 0): array
    {
        //订单总数
        $order_num = app(OrderService::class)->getFilterCount([
            'shop_id' => $shopId,
            'order_status' => [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED],
        ]);
        //订单商品总数
        $order_product_num = OrderItem::hasWhere("orders", function ($query) {
                $query->where("is_del", 0)
                    ->whereIn('order_status',[Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED])
                    ->where('pay_status',Order::PAYMENT_PAID);
            })
            ->where(function ($query) use ($shopId) {
                if ($shopId > -1) {
                    $query->where('OrderItem.shop_id', $shopId);
                }
            })
            ->count();

        //订单总金额
        $order_total_amount = app(OrderService::class)->filterQuery([
            'shop_id' => $shopId,
            'order_status' => [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED],
        ])->sum('total_amount');

        //会员总数
        $user_num = User::count();
        //消费会员总数
        $consumer_membership_num = app(OrderService::class)->filterQuery([
            'order_status' => [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED],
            'shop_id' => $shopId
        ])->group('user_id')->count();

        //人均消费数
        $capita_consumption = $user_num > 0 ? number_format($order_total_amount / $user_num, 2, '.', '') : 0;
        //访问数 -- 商品点击数
        $click_count = app(ProductService::class)->filterQuery([
            'shop_id' => $shopId,
            'is_delete' => 0
        ])->sum('click_count');

        //访问转化率
        $click_rate = $click_count > 0 ? number_format(($order_num / $click_count) * 100, 2, '.', '') : 0;
        //订单转化率
        $order_rate = $click_count > 0 ? number_format(($order_total_amount / $click_count) * 100, 2, '.', '') : 0;
        //消费会员比率
        $consumer_membership_rate = $user_num > 0 ? number_format(($consumer_membership_num / $user_num) * 100, 2, '.', '') : 0;
        //购买率
        $purchase_rate = $user_num > 0 ? number_format(($order_num / $user_num) * 100, 2, '.', '') : 0;
        $result = [
            "order_num" => $order_num,
            "order_product_num" => $order_product_num,
            "order_total_amount" => $order_total_amount,
            "user_num" => $user_num,
            "consumer_membership_num" => $consumer_membership_num,
            "capita_consumption" => $capita_consumption,
            "click_count" => $click_count,
            "click_rate" => $click_rate,
            "order_rate" => $order_rate,
            "consumer_membership_rate" => $consumer_membership_rate,
            "purchase_rate" => $purchase_rate,
        ];
        return $result;
    }

    /**
     * 销售排行
     * @param array $filter
     * @return array
     */
    public function getSalesRanking(array $filter): array
    {
        $start_end_time = [];
        if (!empty($filter["start_time"]) && !empty($filter["end_time"])) {
            $start_end_time = [$filter["start_time"], $filter["end_time"]];
        }

//        $query = OrderItem::hasWhere('orders',function ($query) use ($start_end_time) {
//                $query->where("is_del", 0)
//                    ->whereIn('order_status', [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED])
//                    ->whereBetween('add_time', [Time::toTime($start_end_time[0]), Time::toTime($start_end_time[1])]);
//            })
//            ->field("SUM(quantity * price) AS total_sales_amount,SUM(quantity) AS total_sales_num")
//            ->where(function ($query) use ($filter) {
//                if(!empty($filter["keyword"])){
//                    $query->where("product_name|product_sn", "like", "%{$filter["keyword"]}%");
//                }
//                if(isset($filter['shop_id']) && $filter['shop_id'] > -1){
//                    $query->where("OrderItem.shop_id", $filter['shop_id']);
//                }
//            })
//            ->group("product_id");

        $query = OrderItem::hasWhere('orders', function ($query) use ($start_end_time) {
            $query->where("is_del", 0)
                ->whereIn('order_status', [Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED])
                ->whereBetween('add_time', [Time::toTime($start_end_time[0]), Time::toTime($start_end_time[1])]);
        }, 'product_id')
            ->field("SUM(quantity * price) AS total_sales_amount,SUM(quantity) AS total_sales_num,MAX(OrderItem.product_name) AS product_name,MAX(OrderItem.product_sn) AS product_sn,MAX(OrderItem.sku_data) AS sku_data")
            ->where(function ($query) use ($filter) {
                if (!empty($filter["keyword"])) {
                    $query->where("product_name|product_sn", "like", "%{$filter["keyword"]}%");
                }
                if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
                    $query->where("OrderItem.shop_id", $filter['shop_id']);
                }
            })
            ->group("product_id");

        $count = $query->count();
        $total_list = $query->select()->toArray();
        $list = $query->page($filter["page"],
            $filter["size"])->orderRaw($filter["sort_field"] . ' ' . $filter["sort_order"])->select()->toArray();
        $result = [
            "count" => $count,
            "list" => $list,
        ];

        if ($filter["is_export"]) {
            // 导出
            $data = [];
            foreach ($total_list as $item) {
                if (!empty($item["sku_data"])) {
                    $sku_data = array_map(function ($subArray) {
                        return implode(':', $subArray);
                    }, $item["sku_data"]);
                    $sku_data = implode('|', $sku_data);
                }
                $data[] = [
                    "product_name" => $item["product_name"],
                    "product_sn" => $item["product_sn"],
                    "sku_data" => $sku_data ?? "",
                    "total_sales_num" => $item["total_sales_num"],
                    "total_sales_amount" => $item["total_sales_amount"],
                ];
            }

            app(StatisticsUserService::class)->executeExport($data, 0, 7);
        }
        return $result;
    }


    public function getPanelVendorIndex(int $vendor_id)
    {

        return [
            "console_data" => $this->getVendorConsoleData($vendor_id),
            "real_time_data" => $this->getVendorRealTimeData($vendor_id),
            "panel_statistical_data" => $this->getVendorPanelStatisticalData($vendor_id),
        ];

    }


    /**
     * 获取供应商控制台数据
     */
    public function getVendorConsoleData(int $vendor_id)
    {

         // 待发货订单数量
        $awaitShip=app(Order::class)->where('vendor_id',$vendor_id)->where('order_status',Order::ORDER_CONFIRMED)
            ->where('is_del',0)->where('shipping_status',0)->count();

        // 待结算订单数量
        $awaitSettlement=app(Order::class)->where('vendor_id',$vendor_id)->where('pay_status',Order::PAYMENT_PAID)
            ->where('is_del',0)->where('is_settlement',0)->count();


        // 待处理售后数量
        $awaitAfterSale=app(Aftersales::class)->where('vendor_id',$vendor_id)->where('status',Aftersales::STATUS_WAIT_FOR_SUPPLIER_AUDIT)->count();

        // 售罄SKU数量
        $saleOutProductNum=app(VendorProductSku::class)->where('vendor_id',$vendor_id)->where('sku_stock',0)->count();

        return [
            'awaitShip'=>$awaitShip,
            'awaitSettlement'=>$awaitSettlement,
            'awaitAfterSale'=>$awaitAfterSale,
            'saleOutProductNum'=>$saleOutProductNum,
        ];

    }

    /**
     * 获取供应商实时数据
     */
    public function getVendorRealTimeData(int $vendor_id)
    {
        $today=Time::today();
        $endToday=$today+3600*24;

        // 今日结算总额
        $todaySettlementOrders = app(Order::class)->where('vendor_id', $vendor_id)->where('pay_time', 'between', [$today, $endToday])
            ->where('is_del', 0)->where('is_settlement', 1)->sum('total_amount');


        // 今日结算订单数
        $todaySettlementNum = app(Order::class)->where('vendor_id', $vendor_id)->where('pay_time', 'between', [$today, $endToday])
            ->where('is_del', 0)->where('is_settlement', 1)->count();

        // 在售商品数
        $saleProductNum = app(VendorProduct::class)->where('vendor_id', $vendor_id)->where('product_state', 1)->count();


        // 断供商品数（库存为0的商品）
        $outageProductNum = app(VendorProduct::class)->where('vendor_id', $vendor_id)->where('product_state', 0)->count();

        // 供应商余额
        $accountBalance = app(Vendor::class)->where('vendor_id', $vendor_id)->value('vendor_money');

        // 待结算订单
        $awaitSettlementOrders = app(Order::class)->where('vendor_id', $vendor_id)->where('pay_status', Order::PAYMENT_PAID)
            ->where('is_settlement', 0)->where('is_del', 0)->count();

        // 待结算金额
        $awaitSettlementAmount = app(Order::class)->where('vendor_id', $vendor_id)->where('pay_status', Order::PAYMENT_PAID)
            ->where('is_settlement', 0)->where('is_del', 0)->sum('total_amount');

        // 这里需要根据实际的vendor_shop_bind表来查询
        $bindShopNum = app(VendorShopBind::class)->where('vendor_id', $vendor_id)->count();

        return [
            'today_settlement_orders' => $todaySettlementOrders,
            'today_settlement_num' => $todaySettlementNum,
            'sale_product_num' => $saleProductNum,
            'outage_product_num' => $outageProductNum,
            'account_balance' => $accountBalance,
            'await_settlement_orders' => $awaitSettlementOrders,
            'await_settlement_amount' => $awaitSettlementAmount,
            'bind_shop_num' => $bindShopNum,
        ];
    }

    /**
     * 获取供应商统计图表数据
     */
    public function getVendorPanelStatisticalData(int $vendor_id)
    {

        $today = Time::getCurrentDatetime("Y-m-d");
        $month_day = Time::format(Time::monthAgo(1), "Y-m-d");
        $start_end_time = [strtotime($month_day), strtotime($today)];

        $orderIncomeArr=app(Order::class)->where('vendor_id',$vendor_id)->where('pay_status', Order::PAYMENT_PAID)
                                               ->where('add_time','between',$start_end_time)
                                               ->where('is_del', 0)
                                               ->field(['sum(total_amount) as total_amount','FROM_UNIXTIME(add_time, "%Y-%m-%d") as pay_time','count(1) as num'])
                                               ->group('FROM_UNIXTIME(add_time, "%Y-%m-%d")')->select()->toArray();

        // 横轴
        $horizontal_axis = app(StatisticsUserService::class)->getHorizontalAxis(0, $month_day, $today);
        // 访问统计 -- 纵轴
        $longitudinal_axis_income = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $orderIncomeArr, 0, 4);
        // 订单统计 -- 订单数量
       $longitudinal_axis_order_num = app(StatisticsUserService::class)->getLongitudinalAxis($horizontal_axis, $orderIncomeArr, 0, 5);
        $result = [
            'horizontal_axis'=> $horizontal_axis,
            "longitudinal_axis_income" => $longitudinal_axis_income,
            "longitudinal_axis_order_num" => $longitudinal_axis_order_num,
        ];

        return $result;
    }
}
