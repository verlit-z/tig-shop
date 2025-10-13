<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 购物车
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\front\cart;

use app\model\order\Aftersales;
use app\model\order\Cart;
use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\product\ECard;
use app\model\product\Product;
use app\model\product\ProductAttributes;
use app\model\user\User;
use app\service\admin\merchant\ShopService;
use app\service\admin\product\ProductAttributesService;
use app\service\admin\product\ProductDetailService;
use app\service\admin\product\ProductPriceService;
use app\service\admin\product\ProductStockService;
use app\service\admin\promotion\PointsExchangeService;
use app\service\admin\user\UserCouponService;
use app\service\admin\user\UserRankService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use app\service\front\promotion\PromotionService;
use exceptions\ApiException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use utils\Config;
use utils\Config as UtilsConfig;
use utils\Time;
use utils\Util;

/**
 * 购物车服务类
 */
class CartService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 购物车兼容处理
     * @param int $id
     * @param array $sku_item
     * @param bool $is_quick
     * @param int $type
     * @param int $salesman_id
     * @param string $extra_attr_ids
     * @return bool
     */
    public function addToCart(
        int    $id,
        int    $number,
        int    $sku_id,
        bool   $is_quick = false,
        int    $type = Cart::TYPE_NORMAL,
        int    $salesman_id = 0,
        string $extra_attr_ids = '',
        array  $sku_item = []
    ): bool
    {
        $user_id = request()->userId;
        $this->checkUserCompanyAuth($user_id);
        if (empty($sku_item)) {
            $sku_item = [
                [
                    'sku_id' => $sku_id,
                    'num' => $number
                ]
            ];
        }

        $productDetailService = new ProductDetailService($id);
        $product = $productDetailService->getDetail();
        //检测店铺状态
        //app(ShopService::class)->checkShopStatus($product['shop_id'], $product['product_name']);

        if ($product['product_status'] == 0) {
            throw new ApiException(/** LANG */ \utils\Util::lang('该商品已下架！'));
        }

        $number_arr = array_column($sku_item, 'num');
        $check_number = array_filter($number_arr, function ($num) {
            return $num <= 0;
        });
        if (!empty($check_number)) {
            throw new ApiException(/** LANG */ \utils\Util::lang('商品数量错误！'));
        }

        if ($type == Cart::TYPE_CARD) {
            $card_count = ECard::where(["group_id" => $product['card_group_id'], 'is_use' => 0])->count();

            if (array_sum($number_arr) > $card_count) {
                throw new ApiException(/** LANG */ \utils\Util::lang('该商品卡券库存不足！'));
            }
        }


        $sku_data = [];
        $sku_ids = array_column($sku_item, 'sku_id');
        foreach ($sku_ids as $k => $sku) {
            $sku_data[$k] = $productDetailService->getProductSkuDetail($sku);
        }
        //商品多规格属性
        $extra_sku_data = [];
        if (!empty($extra_attr_ids)) {
            $attr_ids = explode(',', $extra_attr_ids);
            $extra_sku_data = $this->getProductExtraDetail($attr_ids);
        }

        $query = Cart::where('product_id', $id)->where('user_id', $user_id)->where('type', $type);
        if (array_sum($sku_ids) == 0) {
            $query = $query->where('sku_id', 0);
        } else {
            $query = $query->whereIn('sku_id', $sku_ids);
        }
        $item = $query->select();

        $data = [];
        if (!empty($sku_data)) {

            foreach ($sku_data as $k => $sku) {
                $data[$k] = [
                    'user_id' => $user_id,
                    'product_id' => $id,
                    'product_sn' => $product['product_sn'],
                    'pic_thumb' => !empty($sku['pic_thumb']) ? $sku['pic_thumb'] : $product['pic_thumb'],
                    'market_price' => $product['market_price'],
                    'original_price' => $sku['price'],
                    'sku_id' => $sku['id'],
                    'sku_data' => $sku['data'] ?? [],
                    'product_type' => $product['product_type'],
                    'is_checked' => 1,
                    'type' => $type,
                    'shop_id' => $product['shop_id'],
                    'update_time' => Time::now(),
                    'salesman_id' => $salesman_id,
                    'extra_sku_data' => $extra_sku_data
                ];

                if (!$item->isEmpty()) {
                    foreach ($item as $cart_sku) {
                        if ($cart_sku->sku_id == $sku['id']) {
                            if (!$is_quick) {
                                $sku_item[$k]['num'] += $cart_sku->quantity;
                            }
                        }
                    }
                }
                $data[$k]['quantity'] = $sku_item[$k]['num'];
            }
        }

        // 检查库存
        foreach ($data as $val) {
            if (app(ProductStockService::class)->checkProductStock($val['quantity'], $id, $val['sku_id']) === false) {
                throw new ApiException(/** LANG */ \utils\Util::lang('商品:%s 已加购总数达库存上限', '', [$product['product_name']]));
            }
        }
        //检测是否超过商品购买限制
        $cart = Cart::where('product_id', $id)->where('user_id', $user_id)->find();
        $num = array_sum($number_arr);
        if ($is_quick) {
            if($cart) {
                $num =  $num - $cart->quantity <= 0 ? $num : $num - $cart->quantity + 1;
            }

            $this->checkProductLimitNumber($id, $user_id, $num);
            Cart::where('user_id', $user_id)->where('type', $type)->update(['is_checked' => 0]);
        } else {
            if($cart) {
                $num =  $num + $cart->quantity;
            }
            $this->checkProductLimitNumber($id, $user_id, $num);
        }

        if (!$item->isEmpty()) {
            // 删除原购物车数据
            $cart_ids = array_column($item->toArray(), 'cart_id');
            Cart::whereIn('cart_id', $cart_ids)->delete();
        }

        (new Cart)->saveAll($data);

        return true;
    }

    /**
     * 校验用户是否实名认证
     * @param int $user_id
     * @return void
     * @throws ApiException
     */
    public function checkUserCompanyAuth(int $user_id): void
    {
        $user = User::find($user_id);
        if (Config::get("isIdentity") && config('app.IS_B2B') == 1) {
            if (!empty($user) && $user->is_company_auth == 0) {
                throw new ApiException(/** LANG */ \utils\Util::lang('请先完成实名认证'));
            }
        }

    }


    // 获取购物车，返回按店铺分类
    public function getCartListByStore($is_checked = false, $type = Cart::TYPE_NORMAL, $filter = [])
    {
        $cart_list = $this->getCartList($is_checked, $type, $filter = []);
        $carts = [];
        $total = [
            'product_amount' => 0, //商品价格（已选）
            'checked_count' => 0, //商品数（已选）
            'discounts' => 0, //优惠 组合商品（已选）
            'discount_after' => 0,
            'total_count' => 0, //总商品数,
            'discount_coupon_amount' => 0,//优惠券优惠金额
            'discount_discount_amount' => 0,//其他优惠优惠金额
        ];
        foreach ($cart_list as $key => $row) {
            //查询商品信息和查询店铺状态
            $product_info = Product::where('product_id',
                $row['product_id'])->field('product_name,shop_id,product_price,no_shipping,fixed_shipping_type,fixed_shipping_fee,vendor_id,vendor_product_id')->find();
            //app(ShopService::class)->checkShopStatus($product_info['shop_id'], $product_info['product_name']);
            $row['fixed_shipping_type'] = $product_info['fixed_shipping_type'];
            $row['fixed_shipping_fee'] = $product_info['fixed_shipping_fee'];
            $row['vendor_id'] = $product_info['vendor_id']??0;
            $row['vendor_product_id'] = $product_info['vendor_product_id']??0;
            $row['vendor_product_sku_id'] = $row['sku'] ? $row['sku']['vendor_product_sku_id'] : 0;
            $row['is_checked'] = $row['is_checked'] == 1 ? true : false;
            if ($type == Cart::TYPE_EXCHANGE) {
                $row['price'] = $row['sku'] ? $row['sku']['sku_price'] : $product_info['product_price'];
            } else {
                $row['price'] = app(ProductPriceService::class)->getProductFinalPrice($row['product_id'],
                    $row['sku'] ? $row['sku']['sku_price'] : $product_info['product_price'], $row['sku_id'],
                    app(UserService::class)->getUserRankId(request()->userId),
                    app(UserRankService::class)->getUserRankList());
            }

            $row['stock'] = app(ProductStockService::class)->getProductStock($row['product_id'], $row['sku_id'], $type > 1 ? 1 : 0);
            $row['has_sku'] = !empty($row['sku']);

            $row['subtotal'] = bcmul($row['quantity'], $row['price'], 2);
            $row['origin_price'] = $row['price'];
            $row['is_disabled'] = false;
            if ($row['stock'] == 0 || $row['product_status'] == 0) {
                $row['is_disabled'] = true;
                //重置购物车选中状态
                if ($row['is_checked']) {
                    $this->updateCartCheckStatus($row['cart_id']);
                    $row['is_checked'] = false;
                }
            }
            if ($row['has_sku'] && empty($row['sku_id'])) {
                $row['stock'] = 0;
                $row['is_disabled'] = true;
                //重置购物车选中状态
                if ($row['is_checked']) {
                    $this->updateCartCheckStatus($row['cart_id']);
                    $row['is_checked'] = false;
                }
                $row['stock'] = 0;
            }
            $total['total_count'] += $row['quantity'];
            if ($row['is_checked']) {
                $total['checked_count'] += $row['quantity'];
                $total['product_amount'] += $row['subtotal'];
            }
            if (!isset($carts[$row['shop_id']]['no_shipping'])) {
                $carts[$row['shop_id']]['no_shipping'] = 1;
            }
            if ($product_info['no_shipping'] == 0 && $carts[$row['shop_id']]['no_shipping'] == 1) {
                $carts[$row['shop_id']]['no_shipping'] = 0;
            }
            if (!isset($carts[$row['shop_id']]['has_fixed_shipping'])) {
                $carts[$row['shop_id']]['has_fixed_shipping'] = 0;
            }
            if (!isset($carts[$row['shop_id']]['fixed_shipping_fee'])) {
                $carts[$row['shop_id']]['fixed_shipping_fee'] = 0;
            }
            if ($product_info['fixed_shipping_type'] == 1 && $product_info['fixed_shipping_fee'] > 0) {
                $carts[$row['shop_id']]['fixed_shipping_fee'] += $product_info['fixed_shipping_fee'];
            }
            if ($product_info['fixed_shipping_type'] == 1 && $carts[$row['shop_id']]['has_fixed_shipping'] == 0) {
                $carts[$row['shop_id']]['has_fixed_shipping'] = 1;
            }
            $carts[$row['shop_id']]['shop_id'] = $row['shop_id'];
            $carts[$row['shop_id']]['shop_title'] = !empty($row['shop']) ? $row['shop']['shop_title'] : '';
            $carts[$row['shop_id']]['carts'][] = $row;
        }
        // 将结果转换为索引数组
        $carts = array_values($carts);
        return [
            'carts' => $carts,
            'total' => $total,
        ];
    }

    // 获取购物车
    public function getCartList($is_checked = false, $type = Cart::TYPE_NORMAL, $filter = []): array
    {
        $model = Cart::where('user_id', request()->userId)->where('type', $type)
            ->when($is_checked == true, function ($query) {
                $query->where('is_checked', true);
            });
        if (!empty($filter['product_id'])) {
            $model = $model->where('product_id', $filter['product_id']);
        }
        $cart_list = $model->hasWhere('product')
            ->with(['shop', 'product', 'sku'])
            ->order('update_time', 'desc')
            ->select();

        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            if (!empty($cart_list['sku'])) {
                foreach ($cart_list['sku'] as $key => $value) {
                    if (!empty($value['sku_data'])) {
                        $newSkuData = [];
                        foreach ($value['sku_data'] as $k => $eachSku) {
                            foreach ($eachSku as $skuName => $skuValue) {
                                $skuName = Util::lang($skuName, '', [], 8);
                                $skuValue = Util::lang($skuValue, '', [], 8);
                                $newSkuData[$k][$skuName] = $skuValue;
                            }
                        }
                        $cart_list['sku'][$key]['sku_data'] = $newSkuData;
                    }
                }
            }

        }
        return $cart_list ? $cart_list->toArray() : [];
    }

    // 获取购物车数量
    public function getCartCount($is_checked = false, $type = Cart::TYPE_NORMAL, $filter = []): int
    {
//        $quantity = Cart::where('user_id', request()->userId)
//            ->where('is_checked', 1)
//            ->sum('quantity');
//        return $quantity > 0 ? $quantity : 0;
        $total_count = 0;
        $cart_list = $this->getCartList($is_checked, $type, $filter = []);
        foreach ($cart_list as $key => $row) {
            //查询商品信息和查询店铺状态
            $product_info = Product::where('product_id',
                $row['product_id'])->field('product_name,shop_id,product_price')->find();
            //app(ShopService::class)->checkShopStatus($product_info['shop_id'], $product_info['product_name']);
            $row['stock'] = app(ProductStockService::class)->getProductStock($row['product_id'], $row['sku_id'], $type > 1 ? 1 : 0);
            $row['has_sku'] = !empty($row['sku']);
            $row['is_checked'] = $row['is_checked'] == 1 ? true : false;
            $row['is_disabled'] = false;
            if ($row['stock'] == 0 || $row['product_status'] == 0) {
                $row['is_disabled'] = true;
                //重置购物车选中状态
                if ($row['is_checked']) {
                    $this->updateCartCheckStatus($row['cart_id']);
                    $row['is_checked'] = false;
                }
            }
            if ($row['has_sku'] && empty($row['sku_id'])) {
                $row['is_disabled'] = true;
                //重置购物车选中状态
                if ($row['is_checked']) {
                    $this->updateCartCheckStatus($row['cart_id']);
                    $row['is_checked'] = false;
                }
                $row['stock'] = 0;
            }
            $total_count += $row['quantity'];
        }
        return $total_count;
    }

    /**
     * 获取指定购物车的数量
     * @param int $cart_id
     * @return int
     */
    public function getProductCartNum(int $cart_id): int
    {
        $quantity = Cart::where('cart_id', $cart_id)->value('quantity');

        return $quantity ?? 0;
    }

    /**
     * 更新购物车项
     *
     * @param int $cart_id
     * @param array $arr 数组:
     *                    - 'cart_id': 购物车id
     *                    - 'is_checked': 是否选中
     * */
    public function updateCheckStatus(array $arr)
    {
        $data = [];
        $existing_ids = Cart::where('user_id', request()->userId)->column('cart_id');
        foreach ($arr as $key => $value) {
            if (in_array($value['cartId'], $existing_ids)) {
                $data[] = [
                    'cart_id' => intval($value['cartId']),
                    'is_checked' => $value['isChecked'] == 1 ? 1 : 0,
                ];
            }
        }
        $cartModel = new Cart();
        $result = $cartModel->saveAll($data);
        return true;
    }

    /**
     * 更新购物车项
     *
     * @param int $cart_id
     * @param array $arr 数组:
     *                    - 'quantity': 更新的数量
     * @throws ApiException
     */
    public function updateCartItem(int $cart_id, array $arr)
    {
        $data = [
            'quantity' => intval($arr['quantity']),
        ];
        $cart = Cart::where('user_id', request()->userId)->find($cart_id);
        if ($cart) {
            //检测商品库存是否充足
            $stock = app(ProductStockService::class)->getProductStock($cart->product_id, $cart->sku_id);
            if ($stock < intval($arr['quantity'])) {
                throw new ApiException(/** LANG */ \utils\Util::lang('库存不足！'));
            }
            //检测是否超过商品购买限制
            $changeQuantity = intval($arr['quantity']) - $cart->quantity;
            $this->checkProductLimitNumber($cart->product_id, request()->userId, $changeQuantity, $cart_id);

            $cart->save($data);
            return true;
        } else {
            throw new ApiException(/** LANG */ \utils\Util::lang('购物车不存在此商品'));
        }
    }

    /**
     * 删除购物车商品
     *
     * @param int $id
     * @return bool
     */
    public function removeCartItem(array $cart_ids): bool
    {
        if (!$cart_ids) {
            throw new ApiException(/** LANG */ \utils\Util::lang('#id错误'));
        }
        $result = Cart::where('user_id', request()->userId)->whereIn('cart_id', $cart_ids)->delete();
        return $result !== false;
    }

    /**
     * 清空购物车
     * @param int $type
     * @return bool
     */
    public function clearCart(int $type = 1): bool
    {
        $where = [
            ['user_id', '=', request()->userId],
            ['type', '=', $type]
        ];
        $result = Cart::where($where)->delete();
        return $result !== false;
    }

    /**
     * 设置用户购物车选中状态
     * @return bool
     */
    public function is_checked(int $user_id, int $checked): bool
    {
        $result = Cart::where('user_id', $user_id)->update([
            'is_checked' => $checked,
        ]);
        return $result !== false;
    }

    /**
     * 更新购物车状态
     * @param int $cart_id
     * @param int $checked
     * @return void
     */
    public function updateCartCheckStatus(int $cart_id, int $checked = 0): void
    {
        Cart::where('cart_id', $cart_id)->update(['is_checked' => $checked]);
    }

    /**
     * 处理购物车内商品附加属性价格
     * @param $cart
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartPriceByAttrPrice($cart)
    {
        $cart['total']['service_fee'] = '0';
        foreach ($cart['carts'] as $key => &$shopCart) {
            if (empty($shopCart['carts'])) {
                continue;
            }
            foreach ($shopCart['carts'] as $k => $item) {
                $total_attr_price = '0';
                $service_fee = '0';
                $attr_ids = [];
                //返回该产品所有的附加属性
                if (isset($item['extra_sku_data'])) {
                    if (is_array($item['extra_sku_data']) && !empty($item['extra_sku_data'])) {
                        $attr_ids = array_column($item['extra_sku_data'], 'attributesId');
                        foreach ($item['extra_sku_data'] as $ke => $attr_item) {
                            $shopCart['carts'][$k]['extra_sku_data'][$ke]['total_attr_price'] = bcmul(
                                (string)$attr_item['attrPrice'],
                                (string)$item['quantity'],
                                2);
                        }
                    }

                    if (!empty($attr_ids)) {
                        $attr_list = $this->getProductExtraDetail($attr_ids);
                        if (!empty($attr_list)) {
                            foreach ($attr_list as $attr) {
                                $total_attr_price = bcadd($total_attr_price, (string)$attr['attr_price'], 2);
                            }
                        }
                    }

                    $total_attr_price = bcmul($total_attr_price, (string)$item['quantity'], 2);
                    if ($item['is_checked']) {
                        $cart['total']['discount_after'] = bcadd((string)$cart['total']['discount_after'], $total_attr_price, 2);
                        $cart['total']['service_fee'] = bcadd($cart['total']['service_fee'], $total_attr_price, 2);
                    }
                    $shopCart['carts'][$k]['service_fee'] = bcadd($service_fee, $total_attr_price, 2);
                }
                $extra_sku_all_data = app(ProductAttributesService::class)->getAttrList($item['product_id'], '', 2);
                $shopCart['carts'][$k]['extra_sku_all_data'] = $extra_sku_all_data;
            }
        }

        return $cart;
    }

    /**
     * 获取商品附加规格
     * @param array $attr_ids
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductExtraDetail(array $attr_ids)
    {
        $productAttributes = new ProductAttributes();
        return $productAttributes
            ->field('attributes_id, attr_name, attr_value, attr_price')
            ->whereIn('attributes_id', $attr_ids)
            ->select()
            ->toArray();
    }

    /**
     * 初始化以及处理购物车商品的优惠信息
     * @param array $cart
     * @param int $userId
     * @param int $flow_type
     * @param int $use_default_coupon
     * @param array $use_coupon_ids
     * @return array
     */
    public function buildCartPromotion(array $cart, int $userId, int $flow_type = 1, int $use_default_coupon = 1, array $use_coupon_ids = []): array
    {
        //监测商品购买限制和店铺状态
        foreach ($cart['carts'] as $shopCart) {
            if (empty($shopCart['carts'])) {
                continue;
            }
        }

        if ($flow_type != 1) {
            //非普通商品结算不使用优惠（含积分兑换）添加商品多属性规格价格
            return $this->getCartPriceByAttrPrice($cart);
        }

        $cart['total']['discount_seckill_amount'] = 0;
        $cart['total']['discount_product_promotion_amount'] = 0;
        $cart['total']['discount_time_discount_amount'] = 0;
        $cart['total']['discount_discount_amount'] = 0;
        foreach ($cart['carts'] as $cartIndex => $shopCart) {
            if (empty($shopCart['carts'])) {
                continue;
            }
            //按优惠信息分组购物车商品和统计
            $cartPromotionProduct = [];
            $hasDiscountCartId = [];
            $promotions = app(PromotionService::class)->getProductsPromotion($shopCart['carts'],
                $shopCart['shop_id'], 'cart');
            foreach ($promotions as $productPromotionList) {
                if (!$productPromotionList['is_checked'] || empty($productPromotionList['activity_info'])) {
                    continue;
                }
                foreach ($productPromotionList['activity_info'] as $productPromotion) {
                    $cartPromotionProduct[$productPromotion['promotion_id']]['list'][$productPromotionList['cart_id']] = [
                        'cart_id' => $productPromotionList['cart_id'],
                        'product_id' => $productPromotionList['product_id'],
                        'sku_id' => $productPromotionList['sku_id'],
                        'price' => $productPromotionList['price'],
                        'origin_price' => $productPromotionList['origin_price'],
                        'subtotal' => $productPromotionList['subtotal'],
                        'quantity' => $productPromotionList['quantity']
                    ];
                    $cartPromotionProduct[$productPromotion['promotion_id']]['type'] = $productPromotion['type'];
                    $cartPromotionProduct[$productPromotion['promotion_id']]['data'] = $productPromotion['data']->toArray();
                    $cartPromotionProduct[$productPromotion['promotion_id']]['total_quantity'] =
                        isset($cartPromotionProduct[$productPromotion['promotion_id']]['total_quantity']) ? $cartPromotionProduct[$productPromotion['promotion_id']]['total_quantity'] + $productPromotionList['quantity'] : $productPromotionList['quantity'];
                    $cartPromotionProduct[$productPromotion['promotion_id']]['total_subtotal'] = isset($cartPromotionProduct[$productPromotion['promotion_id']]['total_subtotal']) ? $cartPromotionProduct[$productPromotion['promotion_id']]['total_subtotal'] + $productPromotionList['subtotal'] : $productPromotionList['subtotal'];
                }
            }

            $maxCoupon = null;
            foreach ($cartPromotionProduct as &$everyPromotionProduct) {
                if ($everyPromotionProduct['type'] == 2) {//只先计算优惠券的
                    if (!app(UserCouponService::class)->getUserCouponIdByCouponId($userId, $everyPromotionProduct['data']['coupon_id'])) {
                        $everyPromotionProduct['data']['is_receive'] = 0;
                    } else {
                        $everyPromotionProduct['data']['is_receive'] = 1;
                        if ($use_default_coupon == 1) {//如果是默认选中一个，则选最大的
                            $parseData = app(PromotionService::class)->getRealPromotionService($everyPromotionProduct['type'])->parsePrice($everyPromotionProduct,
                                $everyPromotionProduct['data']);
                            if (!empty($parseData) && isset($parseData['promotion']['amount'])) {
                                if ($maxCoupon != null) {
                                    if ($parseData['promotion']['amount'] > $maxCoupon['amount']) {
                                        $maxCoupon = $parseData['promotion'];
                                    }
                                } else {
                                    $maxCoupon = $parseData['promotion'];
                                }
                            }
                        }
                    }

                }

            }
            if ($maxCoupon) {
                $use_coupon_ids[] = $maxCoupon['coupon_id'];
            }
            if (UtilsConfig::get('useCoupon') != 1) {
                $use_coupon_ids = [];
            }


            //计算最大优惠券

            $usedPromotion = [];//已使用的营销
            $enableUsePromotion = [];//可使用的营销

            foreach ($promotions as $pk => $productPromotionList) {

                if (!$productPromotionList['is_checked']) {
                    $promotions[$pk]['activity_info'] = [];
                    continue;
                }
                if (!empty($productPromotionList['activity_info'])) {
                    foreach ($productPromotionList['activity_info'] as $ak => $productPromotion) {
                        //dump($cartPromotionProduct[$productPromotion['promotion_id']]['list']);
                        if ($productPromotion['type'] == 2 && $cartPromotionProduct[$productPromotion['promotion_id']]['data']['is_receive'] == 0 && $productPromotion['is_delete'] == 1) {
                            unset($promotions[$pk]['activity_info'][$ak]);
                            continue;
                        }
                        $parseData = app(PromotionService::class)->getRealPromotionService($productPromotion['type'])->parsePrice($cartPromotionProduct[$productPromotion['promotion_id']],
                            $cartPromotionProduct[$productPromotion['promotion_id']]['data']);
                        if ($productPromotion['type'] == 1) {
                            $promotions[$pk]['is_seckill'] = 1;
                        }
                        if ($productPromotion['type'] == 2 && !empty($parseData)) {
                            $enableUsePromotion[$productPromotion['promotion_id']] = $parseData['promotion'];
                            if (empty($use_coupon_ids) || !in_array($productPromotion['data']['coupon_id'],
                                    $use_coupon_ids)) {
                                continue;
                            }
                        }
                        if (empty($parseData)) {
                            continue;
                        }
                        $parseData['promotion']['promotion_id'] = $productPromotion['promotion_id'];
                        $parseData['promotion']['type'] = $productPromotion['type'];
                        if (isset($usedPromotion[$productPromotion['promotion_id']])) {
                            continue;
                        }
                        $usedPromotion[$productPromotion['promotion_id']] = $parseData['promotion'];
                        if (!empty($parseData['gift'])) {
                            $cart['carts'][$cartIndex]['gift'][$productPromotion['promotion_id']] = $parseData['gift'];
                        }

                        foreach ($parseData['carts'] as $cart_id => $parse) {
                            //因为按优惠券排序后，可能一个商品在多个优惠里面，如果一个优惠一个商品已使用则跳过。继续这样多算一下是因为activity_info 才是真实的优惠使用顺序
                            if (isset($hasDiscountCartId[$cart_id . '_' . $productPromotion['promotion_id']])) {
                                continue;
                            }
                            $hasDiscountCartId[$cart_id . '_' . $productPromotion['promotion_id']] = true;

                            //$promotions[$cart_id]['price'] = Util::number_format_convert($parse['price']);
                            foreach ($cartPromotionProduct as &$p) {
                                if (isset($p['list'][$cart_id])) {
                                    $promotions[$cart_id]['price'] = ceil($parse['price'] * 100) / 100;
                                    $promotions[$cart_id]['subtotal'] = bcmul($promotions[$cart_id]['price'],
                                        $promotions[$cart_id]['quantity'], 2);
                                    $p['list'][$cart_id]['price'] = $promotions[$cart_id]['price'];

                                }
                            }

                            //$cartPromotionProduct[$productPromotion['promotion_id']]['list'][$cart_id]['price'] = $promotions[$cart_id]['price'];

                        }
                    }
                    $promotions[$pk]['activity_info'] = array_values($promotions[$pk]['activity_info']);
                }
            }
            $cart['carts'][$cartIndex]['used_promotions'] = $usedPromotion;
            $cart['carts'][$cartIndex]['enableUsePromotion'] = $enableUsePromotion;
            $cart['carts'][$cartIndex]['gift'] = isset($cart['carts'][$cartIndex]['gift']) ? array_values($cart['carts'][$cartIndex]['gift']) : [];
            if ($cart['carts'][$cartIndex]['gift']) {
                //计算该店铺实付金额
                $shopTotal = 0;
                foreach ($promotions as $promotion) {
                    if ($promotion['is_checked']) {
                        //       $shopTotal += $promotion['price'] * $promotion['quantity'];
                        $shopTotal += $promotion['original_price'] * $promotion['quantity']; //改为优惠前的价格计算礼品
                    }
                }
                foreach ($cart['carts'][$cartIndex]['gift'] as &$gift) {
                    if ($gift['rules_type'] == 0) {
                        $gift['num'] = floor($shopTotal / $gift['minAmount']) * $gift['num'];
                    }
                }
            }
            $cart['carts'][$cartIndex]['carts'] = array_values($promotions);
            $cart['carts'][$cartIndex]['total'] = [
                'discount_coupon_amount' => 0,
                'discount_seckill_amount' => 0,
                'discount_time_discount_amount' => 0,
                'discount_product_promotion_amount' => 0,
                'discount_discount_amount' => 0,
                'discounts' => 0,
                'coupon_ids' => []
            ];
            foreach ($usedPromotion as $promotion) {
                if ($promotion['type'] == 2) {
                    if (in_array($promotion['coupon_id'], $use_coupon_ids)) {
                        $cart['carts'][$cartIndex]['total']['coupon_ids'][] = $promotion['coupon_id'];
                        $cart['carts'][$cartIndex]['total']['discount_coupon_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_coupon_amount'],
                            $promotion['amount'], 2);
                        $cart['total']['discount_coupon_amount'] = (float)bcadd($cart['total']['discount_coupon_amount'],
                            $promotion['amount'], 2);
                    } else {
                        $promotion['amount'] = 0;
                    }
                } elseif ($promotion['type'] == 1) {
                    $cart['total']['discount_seckill_amount'] = (float)bcadd($cart['total']['discount_seckill_amount'],
                        $promotion['amount'], 2);
                    $cart['carts'][$cartIndex]['total']['discount_seckill_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_seckill_amount'],
                        $promotion['amount'], 2);
                } elseif ($promotion['type'] == 6) {
                    $cart['total']['discount_time_discount_amount'] = (float)bcadd($cart['total']['discount_time_discount_amount'],
                        $promotion['amount'], 2);
                    $cart['carts'][$cartIndex]['total']['discount_time_discount_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_time_discount_amount'],
                        $promotion['amount'], 2);
                } elseif (in_array($promotion['type'], [3, 4, 5])) {
                    $cart['total']['discount_product_promotion_amount'] = (float)bcadd($cart['total']['discount_product_promotion_amount'],
                        $promotion['amount'], 2);
                    $cart['carts'][$cartIndex]['total']['discount_product_promotion_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_product_promotion_amount'],
                        $promotion['amount'], 2);
                }
                //优惠券独立计算
                if ($promotion['type'] != 2) {
                    $cart['carts'][$cartIndex]['total']['discount_product_promotion_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_product_promotion_amount'],
                        $promotion['amount'], 2);
                    $cart['carts'][$cartIndex]['total']['discount_discount_amount'] = (float)bcadd($cart['carts'][$cartIndex]['total']['discount_discount_amount'],
                        $promotion['amount'], 2);
                    $cart['total']['discount_discount_amount'] = (float)bcadd($cart['total']['discount_discount_amount'], $promotion['amount'], 2);
                }
                $cart['total']['discounts'] = (float)bcadd($cart['total']['discounts'], $promotion['amount'], 2);
            }

        }

        $cart['total']['product_amount'] = (float)bcadd($cart['total']['product_amount'], 0, 2);
        $cart['total']['discount_after'] = (float)bcsub($cart['total']['product_amount'], $cart['total']['discounts'],
            2);
        $cart['total']['discount_coupon_amount'] = $cart['total']['discount_coupon_amount'] > $cart['total']['product_amount'] ? $cart['total']['product_amount'] : $cart['total']['discount_coupon_amount'];
        $cart['total']['discounts'] = $cart['total']['discounts'] > $cart['total']['product_amount'] ? $cart['total']['product_amount'] : $cart['total']['discounts'];
        return $this->getCartPriceByAttrPrice($cart);
    }


    /**
     * 获取加入购物车商品
     * @param int $product_id
     * @param int $type
     * @return int
     * @throws ApiException
     */
    public function getCartTypeByProduct(int $product_id, int $type): int
    {
        $data = Product::find($product_id);
        if (empty($data)) {
            throw new ApiException(Util::lang('商品不存在'));
        }

        switch ($type) {
            case 2:
                $cart_type = Cart::TYPE_PIN;
                break;
            case 3:
                $cart_type = Cart::TYPE_EXCHANGE;
                break;
            case 4:
                $cart_type = Cart::TYPE_GIFT;
                break;
            case 5:
                $cart_type = Cart::TYPE_BARGAIN;
                break;
            default:
                switch ($data->product_type) {
                    case 1:
                        $cart_type = Cart::TYPE_NORMAL;
                        break;
                    case 2:
                        $cart_type = Cart::TYPE_VIRTUAL;
                        break;
                    case 3:
                        $cart_type = Cart::TYPE_CARD;
                        break;
                    case 4:
                        $cart_type = Cart::TYPE_PAID;
                        break;
                }
                break;
        }
        return $cart_type;
    }

    /**
     * 检查商品购买限制
     * @param int $product_id
     * @param int $user_id
     * @param int $quantity
     * @param int $cart_id
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkProductLimitNumber(int $product_id, int $user_id, int $quantity, int $cart_id = 0)
    {
        //判断是否已购买该产品
        $product_model = new Product();
        $product = $product_model->where('product_id', $product_id)->find();
        if (!$product) {
            return false;
        }
        //查询店铺状态
        //app(ShopService::class)->checkShopStatus($product->shop_id, $product->product_name);

        $limit_number = $product->limit_number;
        if ($limit_number > 0) {
            //再去判断是否有购买过该产品的订单
            $order_model = new Order();
            $order_ids = $order_model
                ->where('user_id', $user_id)
                ->whereIn('pay_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_PROCESSING, Order::PAYMENT_PAID])
                ->whereIn('order_status', [Order::ORDER_PENDING, Order::ORDER_CONFIRMED, Order::ORDER_PROCESSING, Order::ORDER_COMPLETED])
                ->column('order_id');
            $quantity_sum = 0; //已购买的数量
            if (!empty($order_ids)) {
                //这边还要排除售后完成的
                $afterSalesOrderId = Aftersales::whereIn('order_id', $order_ids)
                    ->where('status', Aftersales::STATUS_COMPLETE)
                    ->column('order_id');
                $order_ids = array_diff($order_ids, $afterSalesOrderId);
                $order_item = new OrderItem();
                $quantity_sum = $order_item
                    ->whereIn('order_id', $order_ids)
                    ->where('product_id', $product_id)
                    ->sum('quantity');
            }
            if (empty($cart_id)) {
                //查询购物车内是否已经有该商品，有的话需要减去
                $cartModel = Cart::where('product_id', $product_id)->where('user_id', $user_id)->find();
            } else {
                $cartModel = Cart::where('cart_id', $cart_id)->find();
            }
            $cart_num = 0;
            if ($cartModel) {
                $cart_id = $cart_id > 0 ? $cart_id : $cartModel->cart_id;
                $cart_num = $cartModel->quantity;
            }

            if($quantity > ($limit_number - $quantity_sum - $cart_num)) {
                if ($cart_id > 0) {
                    //更新购物车状态
                    $cart = new Cart();
                    $cart->where('cart_id', $cart_id)->update(['is_checked' => 0]);
                }
                throw new ApiException(Util::lang('您购买的%s数量超过该商品购买限制，请减少数量再下单', '', [$product->product_name]));
            }
        }
        return true;
    }


}
