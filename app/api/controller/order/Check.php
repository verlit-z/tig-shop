<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 商品
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\order;

use app\api\IndexBaseController;
use app\model\order\Cart;
use app\model\user\UserAuthorize;
use app\service\admin\oauth\UserAuthorizeService;
use app\service\admin\order\OrderCheckService;
use app\service\admin\setting\MessageTemplateService;
use app\service\admin\user\UserAddressService;
use app\service\admin\user\UserCouponService;
use app\service\front\cart\CartService;
use think\App;
use think\facade\Request;
use utils\Config;
use utils\Util;

/**
 * 商品控制器
 */
class Check extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 购物车结算
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function index(): \think\Response
    {
        // b2b模式下，判断用户是否实名
        app(CartService::class)->checkUserCompanyAuth(request()->userId);
        $flow_type = $this->request->all('flow_type/d', 1);
        $orderCheckService = new OrderCheckService();
        $cart_list = $orderCheckService->getStoreCarts('', $flow_type);
        if (empty($cart_list['carts'])) {
            return $this->error(Util::lang('您还未选择商品！'));
        }
        $use_coupon_ids = [];
        $select_user_coupon_ids = [];
        $cart_list = app(CartService::class)->buildCartPromotion($cart_list, request()->userId, $flow_type);
        foreach ($cart_list['carts'] as $shopCart) {
            if (isset($shopCart['used_promotions'])) {
                foreach ($shopCart['used_promotions'] as $used_promotion) {
                    if ($used_promotion['type'] == 2) {
                        $use_coupon_ids[] = $used_promotion['coupon_id'];
                        $user_coupon_id = app(UserCouponService::class)->getUserCouponIdByCouponId(request()->userId, $used_promotion['coupon_id']);
                        // 已选择的优惠券id
                        if ($user_coupon_id > 0) $select_user_coupon_ids[] = $user_coupon_id;
                    }
                }
            }
        }
        $params = request()->only([
            'address_id' => 0,
            'shipping_type' => [],
            'pay_type_id' => 1,
            'use_point' => 0,
            'use_balance' => 0,
            'flow_type/d' => 1,
            'use_coupon_ids' => $use_coupon_ids,
            'select_user_coupon_ids' => $select_user_coupon_ids,
            'product_extra' => []
        ]);
//        $params = [
//            'address_id' => $orderCheckService->getSelectedAddressId(),
//            'shipping_type' => $orderCheckService->getSelectedShippingType(),
//            'pay_type_id' => $orderCheckService->getSelectedPayTypeId(),
//            'use_point' => 0,
//            'use_balance' => 0,
//            'use_coupon_ids' => $use_coupon_ids,
//            'select_user_coupon_ids' => $select_user_coupon_ids,
//            'flow_type' => $flow_type
//        ];
        $orderCheckService->initSet($params);
        $result = [
            'address_list' => app(UserAddressService::class)->getAddressList(request()->userId),
            'available_payment_type' => $orderCheckService->getAvailablePaymentType(),
            'store_shipping_type' => $orderCheckService->getStoreShippingType(),
            'cart_list' => $cart_list['carts'],
            'total' => $orderCheckService->getTotalFee($cart_list),
            'balance' => $orderCheckService->getUserBalance(),
            'points' => $orderCheckService->getUserPoints(),
            'available_points' => $orderCheckService->getOrderAvailablePoints(),
            'coupon_list' => $orderCheckService->getCouponListByPromotion($cart_list, $use_coupon_ids, $select_user_coupon_ids),
            'use_coupon_ids' => $use_coupon_ids,
            'select_user_coupon_ids' => $select_user_coupon_ids,
            'tmpl_ids' => app(MessageTemplateService::class)->getMiniProgramTemplateIds(),
            'flow_type' => $flow_type
        ];
        $params['use_point'] = $orderCheckService->getUsePoint();
        $result['item'] = $params;
        return $this->success($result);
    }

    public function update(): \think\Response
    {
        $orderCheckService = new OrderCheckService();
        $params = request()->only([
            'address_id' => 0,
            'shipping_type' => [],
            'pay_type_id' => 1,
            'use_point' => 0,
            'use_balance' => 0,
            'flow_type/d' => 1,
            'use_coupon_ids' => [],
            'product_extra' => []
        ]);

        $orderCheckService->initSet($params);
        //如果有附加属性就更新购物车
        if(!empty($params['product_extra'])) {
            $attr_ids= explode(',', $params['product_extra']['extra_attr_ids']);
            $extra_sku_data = app(CartService::class)->getProductExtraDetail($attr_ids);
            Cart::where('cart_id', $params['product_extra']['cart_id'])->update(['extra_sku_data' => $extra_sku_data]);
        }

        $cart_list = $orderCheckService->getStoreCarts('', $params['flow_type']);
        if (empty($cart_list['carts'])) {
            return $this->error(Util::lang('您还未选择商品！'));
        }

        $cart_list = app(CartService::class)->buildCartPromotion($cart_list, request()->userId, $params['flow_type'], 0,
            $params['use_coupon_ids']);
        $result = [
            'store_shipping_type' => $orderCheckService->getStoreShippingType(),
            'available_payment_type' => $orderCheckService->getAvailablePaymentType(),
            'cart_list' => $cart_list['carts'],
            'total' => $orderCheckService->getTotalFee($cart_list),
            'available_points' => $orderCheckService->getOrderAvailablePoints(),
            'address_list' => app(UserAddressService::class)->getAddressList(request()->userId),
        ];
        return $this->success($result);
    }

    /**
     */
    public function getAvailablePaymentType(): \think\Response
    {
        $orderCheckService = new OrderCheckService();
        return $this->success($orderCheckService->getAvailablePaymentType());
    }

    public function getStoreShippingType(): \think\Response
    {
        $orderCheckService = new OrderCheckService();
        $params = request()->only([
            'address_id' => 0,
            'shipping_type' => [],
            'pay_type_id' => 1,
            'use_point' => 0,
            'use_balance' => 0,
            'flow_type/d' => 1,
            'use_coupon_ids' => [],
            'select_user_coupon_ids' => [],
        ]);
        $orderCheckService->initSet($params);
        return $this->success($orderCheckService->getStoreShippingType($params['flow_type']));
    }


    // 更新优惠券
    public function updateCoupon(): \think\Response
    {
        $orderCheckService = new OrderCheckService();
        $params = request()->only([
            'address_id' => 0,
            'shipping_type' => [],
            'pay_type_id' => 1,
            'use_point' => 0,
            'use_balance' => 0,
            'flow_type/d' => 1,
            'use_coupon_ids' => [],
            'select_user_coupon_ids' => [],
        ]);

        if ($this->request->all('use_default_coupon_ids/d') == 1 && empty($params['use_coupon_ids'])) {
            // 当需要获取默认最优优惠券组合时
            $use_default_coupon = 1;
        } else {
            $use_default_coupon = 0;
        }

        $orderCheckService->initSet($params);
        $cart_list = $orderCheckService->getStoreCarts('', $params['flow_type']);
        if (empty($cart_list['carts'])) {
            return $this->error(Util::lang('您还未选择商品！'));
        }
        $select_user_coupon_ids = $params['select_user_coupon_ids'] ?? [];
        $cart_list = app(CartService::class)->buildCartPromotion($cart_list, request()->userId, $params['flow_type'], $use_default_coupon,
            $params['use_coupon_ids']);
        if ($use_default_coupon == 1) {
            $params['use_coupon_ids'] = [];
            foreach ($cart_list['carts'] as $shopCart) {
                foreach ($shopCart['used_promotions'] as $used_promotion) {
                    if ($used_promotion['type'] == 2) {
                        $params['use_coupon_ids'][] = $used_promotion['coupon_id'];
                        $user_coupon_id = app(UserCouponService::class)->getUserCouponIdByCouponId(request()->userId,
                            $used_promotion['coupon_id']);
                        // 已选择的优惠券id
                        if ($user_coupon_id > 0) {
                            $select_user_coupon_ids[] = $user_coupon_id;
                        }
                    }
                }
            }
        }
        $result = [
            'coupon_list' => $orderCheckService->getCouponListByPromotion($cart_list, $params['use_coupon_ids'],
                $select_user_coupon_ids),
            'use_coupon_ids' => $params['use_coupon_ids'],
            'select_user_coupon_ids' => $select_user_coupon_ids,
            'cart_list' => $cart_list['carts'],
            'available_points' => $orderCheckService->getOrderAvailablePoints(),
            'total' => $orderCheckService->getTotalFee($cart_list),
        ];
        return $this->success($result);
    }

    // 提交订单
    public function submit(): \think\Response
    {
        $orderCheckService = new OrderCheckService();

        $client_type = Util::getClientType();
        if ($client_type == 'wechat') {
            $openid = app(UserAuthorizeService::class)->checkUserIsAuthorize(request()->userId);
            if (!$openid) {
                return $this->error(Util::lang('openid为空！'), 5002);
            }
        }
        $params = request()->only([
            'address_id' => 0,
            'shipping_type' => [],
            'pay_type_id' => 1,
            'use_point' => 0,
            'use_balance' => 0,
            'use_coupon_ids' => [],
            'buyer_note' => '',
            'invoice_data/a' => [],
            'flow_type/d' => 1,
        ]);
        $close_order = Config::get('closeOrder');
        if ($close_order == 1) {
            $this->error(Util::lang('商城正在维护已停止下单！'));
        }
        $orderCheckService->initSet($params);

        $result = $orderCheckService->submit();
        return $this->success([
            'order_id' => $result['order_id'],
            'return_type' => $result['unpaid_amount'] > 0 ? 1 : 2,
        ]);
    }

    /**
     * 记录发票信息
     * @return \think\Response
     */
    public function getInvoice(): \think\Response
    {
        $orderCheckService = new OrderCheckService();
        $params = request()->only([
            "invoice_type/d" => 0,
            "title_type/d" => 0,
        ]);
        $params["user_id"] = request()->userId;
        $item = $orderCheckService->checkInvoice($params);
        return $this->success($item);
    }

}
