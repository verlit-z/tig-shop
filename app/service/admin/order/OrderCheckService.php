<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 订单结算处理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\order;

use AlibabaCloud\Vcs\V20200515\VcsApiResolver;
use app\model\finance\OrderInvoice;
use app\model\finance\UserInvoice;
use app\model\msg\AdminMsg;
use app\model\order\Order;
use app\model\order\OrderAmountDetail;
use app\model\order\OrderCouponDetail;
use app\model\product\ECard;
use app\model\product\ECardGroup;
use app\model\product\Product;
use app\model\setting\ShippingTplInfo;
use app\model\setting\ShippingType;
use app\model\user\User;
use app\model\user\UserCoupon;
use app\service\admin\common\sms\SmsService;
use app\service\admin\finance\OrderInvoiceService;
use app\service\admin\finance\UserInvoiceService;
use app\service\admin\msg\AdminMsgService;
use app\service\admin\product\ECardService;
use app\service\admin\product\ProductService;
use app\service\admin\product\ProductSkuService;
use app\service\admin\promotion\PointsExchangeService;
use app\service\admin\promotion\ProductGiftService;
use app\service\admin\promotion\SeckillService;
use app\service\admin\queue\OrderQueueService;
use app\service\admin\setting\MessageCenterService;
use app\service\admin\setting\ShippingTplService;
use app\service\admin\user\UserAddressService;
use app\service\admin\user\UserCouponService;
use app\service\admin\user\UserRankService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use app\service\front\cart\CartService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Config;
use utils\Config as UtilsConfig;
use utils\TigQueue;
use utils\Time;
use utils\Util;

/**
 * 订单服务类
 */
class OrderCheckService extends BaseService
{

    protected ?array $storeCarts = null;
    protected ?array $address = null;
    protected ?array $regionIds = null;
    protected ?array $cartTotal = null;
    protected ?array $userInfo = null;
    protected ?array $shippingType = null; // 可用的配送方式，按店铺分类
    protected ?array $couponList = null;
    protected ?int $selectedPayTypeId; //已选支付方式
    protected ?array $selectedShippingType; //已选配送类型（按店铺分类）
    protected ?int $selectedAddressId; //已选收货地址ID
    protected ?int $usePoint; //使用积分
    protected ?float $useBalance; //使用余额，此处值为会员当前最大余额，实际使用余额会在total中计算
    protected ?array $useCouponIds;
    protected ?string $buyerNote;
    protected ?array $extension = [];
    protected ?array $invoiceData;
    protected ?int $flowType = 1;

    public function __construct($params = [])
    {
        $this->selectedPayTypeId = null;
        $this->selectedShippingType = null;
        $this->selectedAddressId = null;
        $this->usePoint = null;
        $this->useBalance = null;
        $this->useCouponIds = null;
    }

    public function initSet($params)
    {
        // 设置地址
        $this->setSelectedAddress($params['address_id']);
        // 设置支付方式
        $this->setSelectedPayTypeId($params['pay_type_id']);
        // 设置配送方式
        $this->setSelectedShippingType($params['shipping_type']);
        // 设置使用积分额度
        $this->setUsePoint($params['use_point'], $params['flow_type']);
        // 设置使用余额
        $this->setUseBalance($params['use_balance']);
        // 设置使用优惠券
        $this->setUseCouponIds($params['use_coupon_ids']);

        $this->setFlowType($params['flow_type'] ?? 1);
        // 订单备注
        if (isset($params['buyer_note'])) {
            $this->buyerNote = htmlspecialchars($params['buyer_note']);
        }
        //发票信息
        $this->invoiceData = $params['invoice_data'] ?? [];
    }

    public function setSelectedPayTypeId($type_id)
    {
        if (!in_array($type_id, [PaymentService::PAY_TYPE_ONLINE, PaymentService::PAY_TYPE_COD, PaymentService::PAY_TYPE_OFFLINE])) {
            $this->selectedPayTypeId = 0;
        }
        $this->selectedPayTypeId = $type_id;
        return $this;
    }
    // 设置配送方式
    // data : {type_id:类型id，shop_id:店铺id}
    public function setSelectedShippingType($data)
    {
        $this->selectedShippingType = $data;
        return $this;
    }

    // 设置地址选中
    public function setSelectedAddress(int $address_id)
    {
        if ($address_id > 0) {
            app(UserAddressService::class)->setAddressSelected(request()->userId, $address_id);
        }
        $this->regionIds = null;
        $this->address = null;
        return $this;
    }

    // 设置使用的余额
    public function setUsePoint($point,$flow_type = 1)
    {
        if ($point > 0) {
            $avalibale_point = $this->getOrderAvailablePoints($flow_type);
            if ($avalibale_point < $point) {
                $integralName = UtilsConfig::get('integralName');
                throw new ApiException($integralName.'不足或超过超出单次使用上限');
            }
            $this->usePoint = $point;
        } else {
            $this->usePoint = 0;
        }
    }

    // 设置使用的余额
    public function setUseBalance($balance): self
    {
        if ($balance > 0) {
            $user_balance = $this->getUserBalance();
            if ($user_balance < $balance) {
                $balance = $user_balance;
            }
            $this->useBalance = $balance;
        } else {
            $this->useBalance = 0;
        }
        return $this;
    }

    // 设置使用的优惠券
    public function setUseCouponIds(array $coupon_ids)
    {
        $this->useCouponIds = $coupon_ids;
        return $this;
    }

    public function setFlowType(int $flow_type = 1)
    {
        $this->flowType = $flow_type;
        return $this;
    }

    // 获取购物车选中的商品，按店铺分组
    public function getStoreCarts($data = '', int $flow_type = 1): array
    {
        if ($this->storeCarts === null) {
            $this->storeCarts = app(CartService::class)->getCartListByStore(true, $flow_type);
        }
        return !empty($data) ? $this->storeCarts[$data] : $this->storeCarts;
    }

    // 支付方式
    public function getSelectedPayTypeId(): int
    {
        if ($this->selectedPayTypeId === null) {
            $payment_type = $this->getAvailablePaymentType();
            $this->selectedPayTypeId = $payment_type[0]['type_id'];
        }
        return $this->selectedPayTypeId;
    }

    public function getAvailablePaymentType(): array
    {
        // 判断shipping_type是否全部支持货到付款
        $is_offline = Config::get('useOffline');
        $result = [
            [
                'type_id' => PaymentService::PAY_TYPE_ONLINE,
                'type_name' => Util::lang('在线支付'),
                'disabled' => false,
                'disabled_desc' => '',
                'is_show' => true,
            ],
//            [
//                'type_id' => PaymentService::PAY_TYPE_COD,
//                'type_name' => '货到付款',
//                'disabled' => !$this->isSupportCod(),
//                'disabled_desc' => '商品或所在地区不支持货到付款',
//                'is_show' => true,
//            ],
            [
                'type_id' => PaymentService::PAY_TYPE_OFFLINE,
                'type_name' => Util::lang('线下支付'),
                'disabled' => false,
                'disabled_desc' => '',
                'is_show' => $is_offline ? true : false,
            ],
        ];
        return $result;
    }

    // 配送方式
    public function getSelectedShippingType(): array
    {
        if ($this->selectedShippingType === null) {
            $shipping_type = $this->getStoreShippingType();
            $this->selectedShippingType = [];
            foreach ($shipping_type as $key => $value) {
                if (isset($value[0])) {
                    // 默认第一个
                    $this->selectedShippingType[$key]['type_id'] = isset($value[0]) ? $value[0]['shipping_type_id'] : 0;
                    $this->selectedShippingType[$key]['shop_id'] = isset($value[0]) ? $value[0]['shop_id'] : 0;
                    $this->selectedShippingType[$key]['type_name'] = isset($value[0]) ? $value[0]['shipping_type_name'] : '';
                }
            }
        }
        return $this->selectedShippingType;
    }

    // 获取可用的配送方式，按店铺分组
    public function getStoreShippingType($flow_type = 1): array
    {
        if ($this->shippingType === null) {
            $cart = $this->getStoreCarts('',$flow_type);
            $shipping_type = [];
            $region_ids = $this->getRegionIds();
            foreach ($cart['carts'] as $key => $store) {
                $product_ids = array_unique(array_column($store['carts'], 'product_id'));
                $tpl_ids = $this->getShippingTplIds($store['shop_id'], $product_ids);
                $shipping_type[$store['shop_id']] = $this->getAvailableShippingType($tpl_ids, $region_ids,
                    $store['shop_id']);
            }
            $this->shippingType = $shipping_type;
        }
        return $this->shippingType;
    }

    // 获取选择的地址ID
    public function getSelectedAddressId(): int
    {
        $address = $this->getSelectedAddress();
        $this->selectedAddressId = $address ? $address['address_id'] : 0;
        return $this->selectedAddressId;
    }

    // 获取收货地址
    public function getSelectedAddress(): array
    {
        if ($this->address === null) {
            $this->address = app(UserAddressService::class)->getUserSelectedAddress(request()->userId);
        }
        return $this->address;
    }

    // 获取地区region_ids[number]
    public function getRegionIds(): array
    {
        if ($this->regionIds === null) {
            $region = $this->getSelectedAddress();
            $this->regionIds = isset($region['region_ids']) ? $region['region_ids'] : [];
            //$this->regionIds = [110000];
        }
        return $this->regionIds;
    }

    // 获取店铺下商品的所有运费模板
    public function getShippingTplIds(int $shop_id, array $product_ids): array
    {
        $default_id = app(ShippingTplService::class)->getDefaultShippingTplId($shop_id);
        $tpl_ids = Product::whereIn('product_id', $product_ids)->column('shipping_tpl_id');
        if (in_array(0, $tpl_ids)) {
            $tpl_ids[] = $default_id;
            $tpl_ids = array_filter($tpl_ids, function ($item) {
                return $item !== 0;
            });
        }
        return $tpl_ids ? array_unique($tpl_ids) : [];
    }

    public function getAvailableShippingType($tpl_ids, $regions, $shopId): array
    {
//        $type_list = [];
//        $enabled_tpl_type = [];
//        $types = [];
//        $idx = 0;
//        foreach ($tpl_ids as $key => $tpl_id) {
//            if (Config::get('childAreaNeedRegion', '') == 1) {
//                $tpl_info = ShippingTplInfo::where('shipping_tpl_id', $tpl_id)->where('is_default', 0)->field('region_data,shipping_type_id')->select();
//                if ($tpl_info) {
//                    foreach ($tpl_info as $row) {
//                        if ($this->fetchRegion($regions, isset($row['region_data']['area_regions']) ? $row['region_data']['area_regions'] : [])) {
//                            $enabled_tpl_type[] = $row['shipping_type_id'];
//                        }
//                    }
//                }
//            }
//            $shipping_type_ids = ShippingTplInfo::where('shipping_tpl_id', $tpl_id)->column('DISTINCT shipping_type_id');
//            $types = $idx != 0 ? array_intersect($types, $shipping_type_ids) : $shipping_type_ids; //取交集
//            $idx++;
//        }
//        if (Config::get('shoppingGlobal') == 1) {
//            $type_list = array_intersect($types, $enabled_tpl_type); //取交集，去掉地域不合的模板
//        } else {
//            $type_list = $types;
//        }
        //type_list 按店铺分类的配送类型
        $result = [];
        $result[] = [
            'shipping_type_id' => 1,
            'shop_id' => $shopId,
            'shipping_type_name' => Util::lang(Config::get('defaultLogisticsName')),
        ];
        return $result;
    }

    // 获取订单可用的消费积分
    public function getOrderAvailablePoints($flow_type=1): int
    {
        $user = $this->getUserInfo();
        $cart = $this->getStoreCarts('',$flow_type);
        $cart_total = $cart['total'];
        $product_amount = $cart_total['product_amount'];
        $avalibale_points = $this->getAmountValuePoints($product_amount);
        return min($avalibale_points, $user['points']);
    }

    public function getUserPoints(): int
    {
        $user = $this->getUserInfo();
        return $user['points'] > 0 ? $user['points'] : 0;
    }

    //计算积分能抵多少金额
    public function getPointValueAmount($point = 0)
    {
        $scale = intval(Config::get('integralScale'));
        return $scale > 0 ? round(($point / 100) * $scale, 2) : 0;
    }

    //计算金额需要多少积分
    public function getAmountValuePoints($amount = 0)
    {
        $point_scale = Config::get('integralScale');
        $scale = intval($point_scale);
        return $scale > 0 ? intval($amount / $scale * 100) : 0;
    }

    // 获取用户余额
    public function getUserBalance(): float
    {
        $user = $this->getUserInfo();
        return $user['balance'] > 0 ? $user['balance'] : 0;
    }

    // 是否所有配送方式都支持货到付款
    public function isSupportCod(): bool
    {
        $shipping_type = $this->getStoreShippingType();
        $type = [];
        foreach ($shipping_type as $key => $types) {
            foreach ($types as $_key => $value) {
                if ($value) {
                    $type[$value['shipping_type_id']] = $value['is_support_cod'];
                }
            }
        }
        foreach ($this->getSelectedShippingType() as $key => $value) {
            if (isset($type[$value['type_id']]) && $type[$value['type_id']] == 0) {
                return false;
            }
        }
        return true;
    }

    // 获取用户信息
    public function getUserInfo()
    {
        if ($this->userInfo === null) {
            $user = User::where('user_id', request()->userId)->find();
            if (!$user) {
                throw new ApiException(Util::lang('会员不存在'));
            }
            $user = $user->toArray();
            $this->userInfo = $user;
        }
        return $this->userInfo;
    }

    // 获取订单总金额
    public function getTotalFee(array $cart): array
    {
        $total = [
            'total_amount' => 0, // '订单总金额（商品总价 + 运费等 - 优惠券 - 各种优惠活动）',
            'paid_amount' => 0, // '已支付金额(包含使用余额+线上支付金额+线下支付金额)',
            'unpaid_amount' => 0, // '未付款金额（计算方式为：total_amount - paid_amount）',
            'unrefund_amount' => 0, // '未退款金额（一般出现在订单取消或修改金额、商品后）',
            'coupon_amount' => 0, // '使用优惠券金额 ',
            'points_amount' => 0, // '使用积分金额',
            'balance' => 0, // '使用余额金额',
            'service_fee' => 0, // '服务费',
            'shipping_fee' => 0, // '配送费用',
            'invoice_fee' => 0, // '发票费用',
            'discount_amount' => 0, // '各种优惠活动金额，如折扣、满减等',
            'order_send_point' => 0,
            'store_shipping_fee' => [],
        ];
        $cart_list = $cart['carts'];
        $total = $cart['total'];
        $total['paid_amount'] = 0;
        $total['coupon_amount'] = $total['discount_coupon_amount'];
        $total['service_fee'] = $total['service_fee'];
        $total['discount_amount'] = $total['discount_discount_amount'];
        $total['exchange_points'] = 0;
        // 积分
        $point = $this->usePoint;
        $total['points_amount'] = $this->getPointValueAmount($point);
        //计算各个店铺的优惠
        foreach ($cart_list as $key => $value) {
            $this->extension['coupon_amount'][$value['shop_id']] = 0;
            $this->extension['discount_amount'][$value['shop_id']] = 0;
            if (isset($value['used_promotions'])) {
                foreach ($value['used_promotions'] as $promotion) {
                    if ($promotion['type'] == 2) {
                        //优惠券
                        $this->extension['coupon_amount'][$value['shop_id']] += $promotion['amount'];
                    } else {
                        //折扣
                        $this->extension['discount_amount'][$value['shop_id']] += $promotion['amount'];
                    }
                }
            }
        }
        //积分兑换
        if ($this->flowType == 3) {
            //获取积分兑换的商品
            $exchange_product_id = 0;
            $exchange_product_number = 0;
            foreach ($cart_list as $value) {
                foreach ($value['carts'] as $item) {
                    $exchange_product_id = $item['product_id'];
                    $exchange_product_sku_id = $item['sku_id'];
                    $exchange_product_number += $item['quantity'];
                }
            }
            if ($exchange_product_id > 0) {
                //查询积分兑换信息
                $exchangeInfo = app(PointsExchangeService::class)->getInfoByProductId($exchange_product_id, $exchange_product_sku_id);
                if (isset($exchangeInfo['exchange_integral'])) {
                    $total['exchange_points'] = $exchangeInfo['exchange_integral'] * $exchange_product_number;
                    $total['points_amount'] = $exchangeInfo['points_deducted_amount'] * $exchange_product_number;
                    $this->usePoint = $exchangeInfo['exchange_integral'] * $exchange_product_number;
                }
            }
        }
        // 运费
        $shipping_fee = $this->getShippingFee();
        $total['shipping_fee'] = $shipping_fee['total'];
        $this->extension['shipping_fee'] = $shipping_fee['store_shipping_fee']; // 用于在订单中记录店铺相关的运费信息
        $total['store_shipping_fee'] = $shipping_fee['store_shipping_fee'];

        // 配送方式
        foreach ($this->getSelectedShippingType() as $value) {
            $this->extension['shipping_type'][$value['shop_id']] = [
                'type_id' => $value['type_id'],
                'type_name' => $value['type_name'],
            ];
        }

        // 计算总费用
        $total_amount = round($total['product_amount'] + $total['shipping_fee'] + $total['service_fee'] - $total['points_amount'] - $total['discount_amount'] - $total['coupon_amount'],
            2);
        $total['total_amount'] = $total_amount > 0 ? $total_amount : 0;
        $total['order_send_point'] = app(OrderService::class)->getOrderSendPoint($total['total_amount'],
            request()->userId);
        // 余额
        if ($this->useBalance) {
            $user_balance = $this->getUserBalance();
            if ($total['total_amount'] < $user_balance) {
                //$this->setUseBalance($total['total_amount']);
                $user_balance = $total['total_amount'];
            }
            $total['balance'] = $user_balance;
        } else {
            $total['balance'] = 0;
        }

        $total['unpaid_amount'] = bcsub($total['total_amount'], $total['balance'], 2);

        return $total;
    }

    //根据重量或者件数计算运费
    public function getShippingFee()
    {
        $cart = $this->getStoreCarts();
        $carts = $cart['carts'];
        $cart_total = $cart['total'];
        $data = [];
        $ranks_list = app(UserRankService::class)->getUserRankList();
        $user_rank_id = app(UserService::class)->getUserRankId(request()->userId);
        $free_shipping = false;
        $result = [
            'total' => 0,
            'store_shipping_fee' => [],
        ];
        foreach ($ranks_list as $key => $value) {
            if ($value['rank_id'] == $user_rank_id && $value['free_shipping'] == 1) {
                $free_shipping = true;
            }
        }
        $storeNoShipping = [];
        foreach ($carts as $key => $store) {
            $default_tpl_id = app(ShippingTplService::class)->getDefaultShippingTplId($store['shop_id']);
            $storeNoShipping[$store['shop_id']] = $store['no_shipping'];
            $result['store_shipping_fee'][$store['shop_id']] = 0;
            if ($free_shipping) {
                $result['store_shipping_fee'][$store['shop_id']] = 0;
                continue;
            }
            if ($storeNoShipping[$store['shop_id']] == 1) {
                $result['store_shipping_fee'][$store['shop_id']] = 0;
                continue;
            }
            foreach ($store['carts'] as $_key => $value) {
                if (isset($value['fixed_shipping_type']) && $value['fixed_shipping_type'] == 1) {
                    $result['store_shipping_fee'][$store['shop_id']] += $value['fixed_shipping_fee'];
                    $result['total'] += $value['fixed_shipping_fee'];
                    continue;
                }
                $shipping_tpl_id = $value['shipping_tpl_id'] > 0 ? $value['shipping_tpl_id'] : $default_tpl_id;
                if (!isset($data[$value['shop_id']][$shipping_tpl_id])) {
                    $data[$value['shop_id']][$shipping_tpl_id] = [
                        'weight' => 0,
                        'count' => 0,
                        'fee' => 0,
                    ];
                }
                if (!$value['free_shipping']) {
                    // 不计算包邮的商品数量和重量
                    $data[$value['shop_id']][$shipping_tpl_id]['weight'] += $value['product_weight'] * $value['quantity'];
                    $data[$value['shop_id']][$shipping_tpl_id]['count'] += $value['quantity'];
                }
            }
        }
        $selected_type_ids = [];
        foreach ($this->getSelectedShippingType() as $key => $value) {
            $selected_type_ids[$value['shop_id']] = $value['type_id'];
        }

        foreach ($data as $shop_id => $row) {
            $type_id = $selected_type_ids[$shop_id] ?? 0;

            foreach ($row as $shipping_tpl_id => $value) {
                $tpl_info = [];
                $all_tpl_info = ShippingTplInfo::where([['shipping_type_id', '=', $type_id], ['shipping_tpl_id', '=', $shipping_tpl_id]])->select();
                $all_tpl_info = $all_tpl_info ? $all_tpl_info->toArray() : [];
                foreach ($all_tpl_info as $key => $v) {
                    if ($v['is_default']) {
                        $tpl_info = $v;
                    } else {
                        if ($this->fetchRegion($this->getRegionIds(),
                            isset($v['region_data']['areaRegions']) ? $v['region_data']['areaRegions'] : [])) {
                            $tpl_info = $v;
                        }
                    }
                }
                if ($tpl_info) {
                    // 首件或首重金额
                    $data[$shop_id][$shipping_tpl_id]['fee'] = $value['count'] > 0 ? $tpl_info['start_price'] : 0;
                    if (($cart_total['product_amount'] >= $tpl_info['free_price']) || $tpl_info['is_free']) {
                        $data[$shop_id][$shipping_tpl_id]['fee'] = 0;
                    } else {
                        if ($tpl_info['pricing_type'] == 1) {
                            //按件计费
                            if ($value['count'] - $tpl_info['start_number'] > 0) {
                                $count = @intval(($value['count'] - $tpl_info['start_number']) / $tpl_info['add_number']); //取整，不四舍五入
                                $data[$shop_id][$shipping_tpl_id]['fee'] += $count * $tpl_info['add_price'];
                            }
                        } elseif ($tpl_info['pricing_type'] == 2) {
                            //按重量计费
                            if ($value['weight'] - $tpl_info['start_number'] > 0) {
                                $weight = @intval(($value['weight'] - $tpl_info['start_number']) / $tpl_info['add_number']) + 1; //取整，不四舍五入
                                $data[$shop_id][$shipping_tpl_id]['fee'] += $weight * $tpl_info['add_price'];
                            }
                        }
                    }
                    $result['total'] += $data[$shop_id][$shipping_tpl_id]['fee'];
                    if (!isset($result['store_shipping_fee'][$shop_id])) {
                        $result['store_shipping_fee'][$shop_id] = 0;
                    }
                    $result['store_shipping_fee'][$shop_id] += $data[$shop_id][$shipping_tpl_id]['fee'];
                }
            }

        }
        return $result;
    }

    // 匹配地区
    protected function fetchRegion(array $region, array $regions)
    {
        //dump($region);die();
        //去除国籍编号
        if (isset($region[0]) && strlen($region[0]) < 3) unset($region[0]);
        $region = array_values($region);
        for ($i = 0; $i < count($region); $i++) {
            $current_regions = $region[$i];
            if (in_array($current_regions, $regions)) {
                return true;
            }
            foreach ($regions as $r) {
                if (is_array($r) && in_array($current_regions, $r)) {
                    return true;
                }
            }
        }
        return false;
    }

    // 优惠券
    public function getUseCouponIds()
    {
        if ($this->useCouponIds === null) {
            // 加载列表即可初使化优惠券ids
            $this->getAvailableCouponList();
        }
        return $this->useCouponIds;
    }

    /**
     * 使用优惠里的信息
     * @return array[]
     */
    public function getCouponListByPromotion(array $cart, array $use_coupon_ids, array $select_coupon_ids = []): array
    {
        $couponList = [
            'enable_coupons' => [],
            'disable_coupons' => []
        ];
        $now = Time::now();

        foreach ($cart['carts'] as $store) {
            $coupons = UserCoupon::withJoin('coupon')
                ->where('user_id', request()->userId)
                ->where('shop_id', $store['shop_id'])
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('order_id', 0)
                ->where('used_time', 0)
                ->select()
                ->toArray();
            foreach ($coupons as $key => $value) {
                $coupon = [
                    'id' => $value['coupon_id'],
                    'coupon_name' => $value['coupon']['coupon_name'],
                    'coupon_type' => $value['coupon']['coupon_type'],
                    'min_order_amount' => $value['coupon']['min_order_amount'],
                    'coupon_desc' => $value['coupon']['coupon_desc'],
                    'coupon_money' => $value['coupon_money'],
                    'is_global' => $value['is_global'],
                    'coupon_discount' => $value['coupon_discount'],
                    'shop_id' => $value['coupon']['shop_id'],
                    'end_date' => $value['end_date'],
                    'coupon_id' => $value['coupon_id'],
                    'user_coupon_id' => $value['id'],
//                    'disable_reason' => '',
//                    'disabled' => $value['disabled'],
//                    'selected' => $value['selected'],
                ];
                if (isset($store['enableUsePromotion'])) {
                    foreach ($store['enableUsePromotion'] as $promotion) {
                        if ($promotion['coupon_id'] == $coupon['coupon_id']) {
                            if (in_array($coupon['coupon_id'], $use_coupon_ids) && in_array($coupon['user_coupon_id'], $select_coupon_ids)) {
                                $coupon['selected'] = true;
                            } else {
                                $coupon['selected'] = false;
                            }
                            $couponList['enable_coupons'][] = $coupon;
                            unset($coupons[$key]);
                        }
                    }
                }
            }
            $couponList['disable_coupons'] = array_values($coupons);
        }
        return $couponList;
    }

    // 获取可用的优惠券列表
    public function getAvailableCouponList(): array
    {
        if ($this->couponList === null) {
            $cart = $this->getStoreCarts();
            $carts = $cart['carts'];
            $cart_total = $cart['total'];
            $coupons = [];
            $all_product_ids = [];
            $all_product_id_amounts = [];
            foreach ($carts as $key => $store) {
                $product_amount = 0;
                $product_ids = [];
                $product_id_amounts = []; //按商品id归类的金额
                foreach ($store['carts'] as $_key => $value) {
                    $product_amount += $value['subtotal'];
                    $product_ids[] = $value['product_id'];
                    if (isset($product_id_amounts[$value['product_id']])) {
                        $product_id_amounts[$value['product_id']] += $value['subtotal'];
                    } else {
                        $product_id_amounts[$value['product_id']] = 0;
                    }
                }
                $coupons[] = $this->getStoreCouponList(request()->userId, $store['shop_id'], $product_amount, false,
                    $product_ids, $product_id_amounts);
                $all_product_id_amounts = array_merge($all_product_id_amounts, $product_id_amounts);
                $all_product_ids = array_merge($all_product_ids, $product_ids);
            }
            // 添加全局券
//            $global_coupons = $this->getStoreCouponList(request()->userId, 0, $cart_total['product_amount'], true, $all_product_ids, $all_product_id_amounts);
//            if ($global_coupons) {
//                $coupons = array_merge([$global_coupons], $coupons);
//            }
            // 重新归类
            $result = [
                'enable_coupons' => [],
                'disable_coupons' => [],
            ];
            $selected_coupon_ids = [];
            foreach ($coupons as $key => $value) {
                foreach ($value as $_key => $row) {
                    if ($row['disabled']) {
                        $result['disable_coupons'][] = $row;
                    } else {
                        $result['enable_coupons'][] = $row;
                        if ($row['selected']) {
                            $selected_coupon_ids[] = $row['id'];
                        }

                    }
                }
            }
            $this->useCouponIds = $selected_coupon_ids;
            $this->couponList = $result;
        }
        return $this->couponList;
    }
    //

    /**
     * 获取店铺的优惠券
     *
     * @param [type] $user_id
     * @param integer $shop_id
     * @param integer $product_amount 商品总金额
     * @param boolean $is_global 是否全局
     * @param array $product_ids 商品id
     * @param array $product_id_amounts 商品金额 [[id=>amount]]
     * @return array
     */
    protected function getStoreCouponList(
        $user_id,
        $shop_id = 0,
        $product_amount = 0,
        $is_global = false,
        $product_ids = [],
        $product_id_amounts = []
    ): array
    {

        $now = Time::now();
        $selected = false; //该店铺是否有已选择的优惠券
        $coupon = UserCoupon::withJoin('coupon')
            ->where('user_id', request()->userId)
            ->where('shop_id', $shop_id)
            ->where('is_global', $is_global)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('order_id', 0)
            ->where('used_time', 0)
            ->select()
            ->toArray();
        $max_money_coupon_key = null;
        $result = [];
        foreach ($coupon as $key => $value) {
            $value['disabled'] = false;
            $value['disable_reason'] = '';
            $value['selected'] = false;
            $range_ids = (array)$value['send_range_data'];
            if ($value['coupon']['send_range'] == 1) {
            }
            if ($value['coupon']['send_range'] == 3) {
                // 只包含指定商品
                if (!array_intersect($product_ids, $range_ids)) {
                    $value['disabled'] = true;
                    $value['disable_reason'] = /** LANG */
                        '所结算商品中没有指定的商品';
                }
                $product_amount = 0;
                foreach ($product_id_amounts as $product_id => $amount) {
                    if (in_array($product_id, $range_ids)) {
                        $product_amount += $amount;
                    }

                }
            } elseif ($value['coupon']['send_range'] == 4) {
                // 排除指定商品
                if (!array_diff($product_ids, $range_ids)) {
                    $value['disabled'] = true;
                    $value['disable_reason'] = /** LANG */
                        '所结算商品中没有指定的商品';
                }
                foreach ($product_id_amounts as $product_id => $amount) {
                    if (in_array($product_id, $range_ids)) {
                        $product_amount -= $amount;
                    }
                }
            }
            if ($value['coupon_type'] == 2) {
                // 折扣券
                $coupon[$key]['coupon_money'] = $value['coupon_money'] = round($product_amount - round($product_amount * $value['coupon_discount'] * 10 / 100, 2), 2);
            }

            // 标记已选优惠券
            $use_coupon_ids = $this->useCouponIds ? $this->useCouponIds : [];
            if (in_array($value['id'], $use_coupon_ids)) {
                $value['selected'] = true;
                $selected = true;
            }

            // 判断金额未达到的优惠券并标记disabled
            if ($value['disabled'] == false && $value['coupon']['min_order_amount'] > $product_amount) {
                $value['disabled'] = true;
                $value['disable_reason'] = /** LANG */
                    '指定商品差' . $value['coupon']['min_order_amount'] . '可用该券';
            }

            // 记录可选优惠券里最大金额的优惠券，以供默认选择最大金额的
            if ($value['disabled'] == false) {
                if ($max_money_coupon_key === null) {
                    $max_money_coupon_key = $key;
                } else {
                    if ($value['coupon_money'] > $coupon[$max_money_coupon_key]['coupon_money']) {
                        $max_money_coupon_key = $key;
                    }
                }
            }
            $result[] = [
                'id' => $value['id'],
                'coupon_name' => $value['coupon']['coupon_name'],
                'coupon_type' => $value['coupon']['coupon_type'],
                'min_order_amount' => $value['coupon']['min_order_amount'],
                'coupon_desc' => $value['coupon']['coupon_desc'],
                'coupon_money' => $value['coupon_money'],
                'is_global' => $value['is_global'],
                'coupon_discount' => $value['coupon_discount'],
                'shop_id' => $value['coupon']['shop_id'],
                'end_date' => $value['end_date'],
                'coupon_id' => $value['coupon_id'],
                'disable_reason' => $value['disable_reason'],
                'disabled' => $value['disabled'],
                'selected' => $value['selected'],
            ];
        }
        // 当有可选优惠券且店铺未选时，选择最大的
        if ($result && $selected == false && $max_money_coupon_key !== null && $this->useCouponIds === null) {
            $result[$max_money_coupon_key]['selected'] = true;
        }
        // 按金额大小排序
        if ($result) {
            array_multisort(array_column($result, 'coupon_money'), SORT_DESC, $result);
        }
        return $result;
    }

    public function submit()
    {
        $cart = $this->getStoreCarts('', $this->flowType);
        $userId = request()->userId;
        $couponId = $this->useCouponIds;
        $flowType = $this->flowType;
        $result = tig_batch_task([
            'cart' => function () use ($cart, $userId, $couponId, $flowType) {
                return app(CartService::class)->buildCartPromotion($cart, $userId, $flowType, 0, $couponId);
            },
            'address' => function () {
                return app(OrderCheckService::class)->getSelectedAddress();
            }
        ]);
        $cart = $result['cart'];
        $address = $result['address'];

        $carts = $cart['carts'];
        if(empty($carts)) {
            throw new ApiException(/** LANG */ \utils\Util::lang('购物车不存在此商品'));
        }
        $cart_total = $cart['total'];
        $item_data = [];
        $cart_ids = [];
        $salesmanOrders = [];
        $orderAmountDetailData = [];
        $orderCouponDetailData = [];
        $e_card = [];
        $defaultVendorId = 0;
        foreach ($carts as $key => $store) {
            $orderAmountDetailData[] = [
                'shop_id' => $store['shop_id'],
                'discount_amount' => $store['total']['discount_discount_amount'] ?? 0,
                'coupon_amount' => $store['total']['discount_coupon_amount'] ?? 0,
                'time_discount_amount' => $store['total']['discount_time_discount_amount'] ?? 0,
                'shipping_fee' => $this->extension['shipping_fee'][$store['shop_id']] ?? '',
            ];
            if (isset($store['total']['coupon_ids'])) {
                foreach ($store['total']['coupon_ids'] as $coupon_id) {
                    $orderCouponDetailData[] = [
                        'shop_id' => $store['shop_id'],
                        'coupon_id' => $coupon_id
                    ];
                }
            }
            foreach ($store['carts'] as $value) {
                if ($value['product_status'] == 0) {
                    throw new ApiException(Util::lang('%s 商品已下架~', '', [$value['product_name']]));
                }
                if ($value['stock'] < $value['quantity']) {
                    throw new ApiException(Util::lang('%s 商品库存不足~', '', [$value['product_name']]));
                }
                app(CartService::class)->checkProductLimitNumber($value['product_id'], request()->userId,
                    $value['quantity'], $value['cart_id']);

                $cart_ids[] = $value['cart_id'];
                $defaultVendorId = $value['vendor_id'];
                $item_data[] = [
                    'user_id' => request()->userId, //会员id
                    'price' => $value['price'], //商品最终单价
                    'quantity' => $value['quantity'], // 商品的购买数量
                    'product_id' => $value['product_id'], //商品的的id
                    'product_name' => $value['product_name'], // 商品的名称
                    'product_sn' => $value['product_sn'], // 商品的唯一货号
                    'pic_thumb' => $value['pic_thumb'], // 商品缩略图
                    'sku_id' => $value['sku_id'], //规格ID
                    'sku_data' => $value['sku_data'], //购买该商品时所选择的属性
                    'product_type' => $value['product_type'], // 是否是实物
                    'shop_id' => $value['shop_id'], //店铺id
                    'type' => $value['type'], //商品类型
                    'promotion_data' => $value['activity_info'] ?? [],
                    'origin_price' => $value['origin_price'] ?? 0,
                    'is_seckill' => $value['is_seckill'] ?? 0,
                    'extra_sku_data' => $value['extra_sku_data'],
                    'vendor_product_id' => $value['vendor_product_id'] ?? 0,
                    'vendor_id' => $value['vendor_id'] ?? 0,
                    'vendor_product_sku_id' => $value['vendor_product_sku_id'] ?? 0,
                    'suppliers_id' => $value['suppliers_id'] ?? 0,
                    'card_group_name' => ECardGroup::where("group_id",$value['card_group_id'])->value('group_name') ?? "",
                    // 'prepay_price' => $value['prepay_price'], // 预售价格todo
                ];
                if (!empty($value['salesman_id'])) {
                    $salesmanOrders[$value['product_id'] . '_' . $value['sku_id']] = $value['salesman_id'];
                }
                // 为卡密商品订单时分配电子卡券
                if ($value['product_type'] == Product::PRODUCT_TYPE_CARD) {
                    $e_card = app(ECardService::class)->getNewCardByGroupId($value['card_group_id'],$value['quantity']);
                    if (count($e_card) < $value['quantity']) {
                        throw new ApiException(Util::lang('电子卡券库存不足'));
                    }
                }

            }
            if (!empty($store['gift'])) {//有赠品
                foreach ($store['gift'] as $gift) {
                    $item_data[] = [
                        'user_id' => request()->userId, //会员id
                        'price' => 0, //商品最终单价
                        'quantity' => $gift['num'], // 商品的购买数量
                        'product_id' => $gift['product_id'], //商品的的id
                        'product_name' => $gift['product_name'], // 商品的名称
                        'product_sn' => $gift['product_sn'], // 商品的唯一货号
                        'pic_thumb' => $gift['pic_thumb'], // 商品缩略图
                        'sku_id' => $gift['sku_id'], //规格ID
                        'sku_data' => $gift['sku_data'], //购买该商品时所选择的属性
                        'product_type' => $gift['product_type'], // 是否是实物
                        'shop_id' => $gift['shop_id'], //店铺id
                        'type' => $gift['type'], //商品类型
                        'is_gift' => 1,
                        'promotion_data' => $gift,
                        // 'prepay_price' => $value['prepay_price'], // 预售价格todo
                    ];
                }
            }
        }
        $orderService = new OrderService();
        $userService = app(userService::class);

        $total = $this->getTotalFee($cart);
        // 扩展信息
        $data = [
            'order_sn' => $orderService->creatNewOrderSn(), //订单号,唯一
            'user_id' => request()->userId, //用户id,同users的user_id
            'order_status' => Order::ORDER_PENDING, //订单的状态
            'shipping_status' => Order::SHIPPING_PENDING, //商品配送情况
            'pay_status' => Order::PAYMENT_UNPAID, //支付状态
            'add_time' => Time::now(), //订单生成时间
            'consignee' => $address['consignee'], //收货人的姓名
            'region_ids' => $address['region_ids'], //[JOSN]地区id数组
            'region_names' => $address['region_names'], //[JOSN]地区name数组
            'address' => $address['address'], //详细地址
            'address_data' => $address, //存JSON
            'mobile' => $address['mobile'], //收货人的手机
            'email' => $address['email'], //收货人的Email
            'buyer_note' => $this->buyerNote, //买家备注
            'pay_type_id' => $this->getSelectedPayTypeId(), //支付类型
            'use_points' => $this->usePoint, //使用的积分的数量
            'referrer_user_id' => $userService->getUserReferrerId(request()->userId), //推广人userId
            'shop_id' => 0, //店铺id
            'shop_title' => '', //店铺名称
            'is_store_splited' => 0, //是否已按店铺拆分:0,否;1,是
            'total_amount' => $total['total_amount'], //订单总金额（商品总价 + 运费等 - 优惠券 - 各种优惠活动）
            'paid_amount' => $total['paid_amount'], //已支付金额(包含使用余额+线上支付金额+线下支付金额)
            'unpaid_amount' => $total['unpaid_amount'], //未付款金额（计算方式为：total_amount - paid_amount）
            'product_amount' => $total['product_amount'], //商品的总金额
            'coupon_amount' => $total['coupon_amount'], //使用优惠券金额
            'points_amount' => $total['points_amount'], //使用积分金额
            'balance' => $total['balance'], //使用余额金额
            'service_fee' => $total['service_fee'], //服务费
            'shipping_fee' => $total['shipping_fee'], //配送费用
            'invoice_fee' => 0, //发票费用
            'discount_amount' => $total['discount_amount'], //各种优惠活动金额，如折扣、满减等 todo
            'order_extension' => $this->extension, //[JSON]记录订单使用的优惠券、优惠活动、不同店铺配送等的具体细节信息
            'order_source' => \utils\Util::getClientType(), //下单来源设备，APP|PC|H5|微信公众号|微信小程序
            'invoice_data' => isset($this->invoiceData["invoice_type"]) ? $this->invoiceData : [],
            'is_exchange_order' => $total['exchange_points'] > 0 ? 1 : 0,
            'order_type' => $flowType,
            'vendor_id' => $defaultVendorId,
        ];
        if (count($carts) === 1) {
            // 所有商品都是来自同一店铺时
            //$data['is_store_splited'] = 1;
            $data['shop_id'] = $carts[0]['shop_id'];
            // 更新订单配送类型
            $shipping_type = $this->getSelectedShippingType();
            $data['shipping_type_id'] = $shipping_type[$data['shop_id']]['type_id'] ?? 0;
            $data['shipping_type_name'] = $shipping_type[$data['shop_id']]['type_name'] ?? '';
        }

        $order = new Order();
        // 检查订单编号是否已存在
        while ($order->where('order_sn', $data['order_sn'])->find()) {
            $data['order_sn'] = $orderService->creatNewOrderSn();
        }

        try {
            // 启动事务
            Db::startTrans();
            // 创建订单
            $order->save($data);
            //使用了积分减积分
            if ($data['use_points'] > 0) {
                $integralName = UtilsConfig::get('integralName');
                $userService->decPoints($data['use_points'], $data['user_id'], '订单支付扣除'. $integralName);
            }
            //使用了余额减余额
            $is_balance = false;
            if ($data['balance'] > 0) {
                $userService->decBalance($data['balance'], $data['user_id'], '订单支付扣除余额');
                $is_balance = true;
            }

            if($is_balance) {
                $this->sendPaySuccessSms($data['user_id'], $data['order_sn']);
            } else {
                //发送下单成功短信
                $this->sendCreateSms($data['user_id'], $data['order_sn']);
            }

            //减购物车
            if (!empty($cart_ids)) {
                app(CartService::class)->removeCartItem($cart_ids);
            }
            //设置优惠券已使用
            if ($data['coupon_amount'] > 0) {
                app(UserCouponService::class)->useCoupon($this->useCouponIds, $data['user_id'], $order->order_id);
            }
            // 添加订单商品
            foreach ($item_data as $key => $value) {
                $item_data[$key]['order_id'] = $order->order_id;
                $item_data[$key]["order_sn"] = $order->order_sn;

                //增加秒杀销量
                app(SeckillService::class)->incSales($value['product_id'], $value['sku_id'], $value['quantity']);
                //减少秒杀库存
                app(SeckillService::class)->decStock($value['product_id'], $value['sku_id'], $value['quantity']);
                //增加商品销量
                app(ProductService::class)->incSales($value['product_id'], $value['quantity']);
                //减少赠品库存   赠品扣赠品库存 商品扣商品库存 别商品库存都扣了
                if (isset($value['is_gift']) && $value['is_gift'] == 1 && !empty($value['promotion_data']['giftId'])) {
                    app(ProductGiftService::class)->decStock($value['promotion_data']['giftId'], $value['quantity']);
                } else {
                    //减库存
                    if (!empty($value['sku_id'])) {
                        app(ProductSkuService::class)->decStock($value['sku_id'], $value['quantity'], $value['shop_id']);
                    } else {
                        app(ProductService::class)->decStock($value['product_id'], $value['quantity'], $value['shop_id']);
                    }
                }

                if (!empty($value['sku_id'])) {
                    $product_stock = app(ProductSkuService::class)->getStock($value['sku_id']);
                } else {
                    $product_stock = app(ProductService::class)->getStock($value['product_id']);
                }

                // 商品库存预警 + 无货 --- 发送后台信息
                if (0 < $product_stock && $product_stock <= 100) {
                    app(AdminMsgService::class)->createMessage([
                        "msg_type" => AdminMsg::MSG_TYPE_PRODUCT_LOW_STOCK,
                        "shop_id" => $value['shop_id'],
                        "vendor_id" => $value['vendor_id'],
                        "product_id" => $value['product_id'],
                        'title' => "商品库存预警:{$value['product_name']}",
                        'content' => "您的商品【{$value['product_name']}】库存不足,还剩{$product_stock},预警库存为100,请及时补充库存!",
                        'related_data' => ["product_id" => $value['product_id']]
                    ]);
                } elseif ($product_stock == 0) {
                    app(AdminMsgService::class)->createMessage([
                        "msg_type" => AdminMsg::MSG_TYPE_PRODUCT_NO_STOCK,
                        "shop_id" => $value['shop_id'],
                        "vendor_id" => $value['vendor_id'],
                        "product_id" => $value['product_id'],
                        'title' => "商品无货:{$value['product_name']}",
                        'content' => "您的商品【{$value['product_name']}】库存已经无货，请及时补充库存!",
                        'related_data' => ["product_id" => $value['product_id']]
                    ]);
                }
            }
            $items = $order->items()->saveAll($item_data);
            foreach ($items as $key => $value) {
                if (isset($salesmanOrders[$value['product_id'] . '_' . $value['sku_id']])) {
                    $value['salesman_id'] = $salesmanOrders[$value['product_id'] . '_' . $value['sku_id']];
                    app(\app\service\admin\salesman\OrderService::class)->create($value);
                    app(TigQueue::class)->push(SalesmanUpgradeJob::class, [
                        'salesmanId' => $value['salesman_id']
                    ]);
                }

                // 封装卡密信息
                if (!empty($e_card)) {
                    foreach ($e_card as $e => $card) {
                        $card['is_use'] = 1;
                        $card['order_id'] = $order->order_id;
                        $card['order_item_id'] = $value['item_id'];
                        $e_card[$e] = $card;
                    }
                }
            }
            $orderDetailService = app(OrderDetailService::class)->setId($order->order_id);
            $orderDetailService->addLog(/** LANG */ '会员提交订单');

            if ($total['discount_amount'] || $total['coupon_amount']) {
                //保存订单优惠明细order_amount_detail
                foreach ($orderAmountDetailData as $orderAmountDetail) {
                    $orderAmountDetail['order_id'] = $order->order_id;
                    OrderAmountDetail::create($orderAmountDetail);
                }
            }
            if ($total['coupon_amount']) {
                //保存订单优惠券记录
                foreach ($orderCouponDetailData as $orderCouponDetail) {
                    $orderCouponDetail['order_id'] = $order->order_id;
                    OrderCouponDetail::create($orderCouponDetail);
                }
            }
            // 添加发票申请
            if (!empty($this->invoiceData) && isset($this->invoiceData["invoice_type"]) && $this->invoiceData["invoice_type"]) {
                $invoice_data = $this->invoiceData;
                $invoice_data["user_id"] = request()->userId;
                $invoice_data["status"] = 0;
                $invoice_data["order_id"] = $order->order_id;
                $invoice_data["amount"] = $order->total_amount;

                if ($this->invoiceData["invoice_type"] == 2) {
                    $invoice_data['title_type'] = 2;
                }
                $invoice = OrderInvoice::create($invoice_data);
                if ($invoice) {
                    $order->invoice_data = $invoice_data;
                    $order->save();
                }

                $userInfo = User::find($invoice_data["user_id"]);
                // 发票申请 -- 发送后消息
                app(AdminMsgService::class)->createMessage([
                    "msg_type" => AdminMsg::MSG_TYPE_INVOICE_APPLY,
                    "order_id" => $order->order_id,
                    'title' => "您有新的发票申请,申请金额：{$invoice_data["amount"]},发票抬头:{$invoice_data['company_name']}",
                    'content' => "用户【{$userInfo["username"]}】针对订单【{$order->order_sn}】提交了发票申请",
                    'related_data' => [
                        "order_id" => $order->order_id,
                        "order_invoice_id" => $invoice->id
                    ]
                ]);
            }

            app(MessageCenterService::class)->sendUserMessage($order->user_id, $order->order_id, 1);
            if ($data['unpaid_amount'] <= 0) {
                app(OrderDetailService::class)->setOrderId($order->order_id)->updateOrderMoney();
                //已支付发送支付消息
                app(MessageCenterService::class)->sendUserMessage($order->user_id, $order->order_id, 2);

                // 支付完成若是卡券则分配卡券
                if (!empty($e_card)) {
                    (new ECard)->saveAll($e_card);
                }

                // 订单交易成功获取成长值
                app(UserRankService::class)->getRankGrowth($userId);
            }
            if ($data['unpaid_amount'] > 0 && $data['pay_type_id'] != PaymentService::PAY_TYPE_OFFLINE) {
                //加入队列
                app(OrderQueueService::class)->cancelUnPayOrderJob($order->order_id);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            if (strpos($e->getMessage(), "UNSIGNED Value Is Out") !== false) {
                throw new ApiException(Util::lang('库存不足'));
            } else {
                throw new ApiException(Util::lang($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString()));
            }

        }
        return [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'unpaid_amount' => $data['unpaid_amount'],
        ];
    }

    public function sendCreateSms($user_id, $order_sn)
    {
        $user = User::findOrEmpty($user_id);
        if(empty($user)) {
            return false;
        }

        if(!empty($user['mobile'])){
            app(SmsService::class)->sendSms($user['mobile'], 'user_order', [$order_sn]);
        }
        //给用户发送开票成功短信
        return true;

    }


    public function sendPaySuccessSms($user_id, string $order_sn)
    {
        $user = User::findOrEmpty($user_id);
        if(empty($user)) {
            return false;
        }
        if(!empty($user['mobile'])){
            app(SmsService::class)->sendSms($user['mobile'], 'user_pay', [$order_sn]);
        }
        return true;
    }


    /**
     * 记录发票信息
     * @param array $params
     * @return mixed
     */
    public function checkInvoice(array $params): mixed
    {
        $query = app(OrderInvoiceService::class)->filterQuery($params);
        $result = $query->where("order_invoice.status", 1)->order("id", "desc")->limit(1)->findOrEmpty()->toArray();
        //如果没找到就去user_invoice表去找
        if(empty($result) && $params['title_type'] == 2){
            $userInvoice = UserInvoice::where("user_id", $params['user_id'])
                ->where("status", 1)
                ->order("invoice_id", "desc")
                ->limit(1)
                ->findOrEmpty()
                ->toArray();
            if(!empty($userInvoice)){
                $result  = $userInvoice;
            }
        }


        return !empty($result) ? $result : false;
    }


    /**
     * 获取使用积分
     * @return int|nul
     */
    public function getUsePoint()
    {
        return $this->usePoint;
    }
}
