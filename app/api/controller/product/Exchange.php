<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 商品积分兑换
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\product;

use app\api\IndexBaseController;
use app\model\order\Cart;
use app\service\admin\product\ProductDetailService;
use app\service\admin\product\ProductStockService;
use app\service\admin\promotion\PointsExchangeService;
use app\service\admin\user\UserInfoService;
use app\service\front\cart\CartService;
use exceptions\ApiException;
use think\App;
use utils\Config as UtilsConfig;
use utils\Util;

/**
 * 商品控制器
 */
class Exchange extends IndexBaseController
{
    protected PointsExchangeService $pointsExchangeService;

    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app, PointsExchangeService $pointsExchangeService)
    {
        parent::__construct($app);
        $this->pointsExchangeService = $pointsExchangeService;
    }

    /**
     * 列表
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'is_enabled/d' => 1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->pointsExchangeService->getFilterResult($filter);
        $total = $this->pointsExchangeService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function detail(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $exchangeInfo = $this->pointsExchangeService->getDetail($id);
        $productDetailService = new ProductDetailService($exchangeInfo["product_id"]);
        $product = $productDetailService->getDetail();
        if ($product['is_delete'] == 1 || $product['product_status'] != 1){
            return $this->error(Util::lang('商品不存在'));
        }
        return $this->success([
            'item' => $product,
            "exchange_info" => $exchangeInfo,
            'desc_arr' => $productDetailService->getDescArr(),
            'sku_list' => $productDetailService->getSkuList(),
            'pic_list' => $productDetailService->getPicList(),
            'attr_list' => $productDetailService->getAttrList(),
            'rank_detail' => $productDetailService->getProductCommentRankDetail(),
            'service_list' => $productDetailService->getServiceList(),
            'checked_value' => $productDetailService->getSelectValue($exchangeInfo['sku_id']),
            'consultation_total' => $productDetailService->getConsultationCount(),
        ]);
    }

    public function addToCart(): \think\Response
    {
        $params = request()->only([
            'id/d' => 0,
            'sku_id/d' => 0,
            'number/d' => 0,
            'salesman_id/d' => 0,
            'extra_attr_ids' => '',
        ]);
        $item = $this->pointsExchangeService->getDetail($params['id']);
        if (empty($item['product_id'])) throw new ApiException(lang('活动不存在'));
        if (empty($item['is_enabled'])) throw new ApiException(lang('活动已下架'));
        $productDetailService = new ProductDetailService($item["product_id"]);
        $product = $productDetailService->getDetail();
        if (empty($product)) throw new ApiException(lang('未找到兑换商品'));
        if ($product['is_delete'] == 1 || $product['product_status'] != 1){
            return $this->error(Util::lang('商品不存在'));
        }
        //检测库存
        $stock = app(ProductStockService::class)->getProductStock($item['product_id'], $params['sku_id'], 1);
        if ($params['number'] > $stock) throw new ApiException(lang('库存不足'));
        //检测积分是否充足
        $userInfoService = new UserInfoService(request()->userId);
        $users = $userInfoService->getBaseInfo();
        $integralName = UtilsConfig::get('integralName');
        if ($params['number'] * $item['exchange_integral'] > $users['points']) throw new ApiException(lang($integralName.'不足!'));
        // 获取购物车类型
        $cart_type = app(CartService::class)->getCartTypeByProduct($item['product_id'],Cart::TYPE_EXCHANGE);
        //1.清除购物车积分商品
        app(CartService::class)->clearCart($cart_type);
        //2.加入购物车
        app(CartService::class)->addToCart($item['product_id'], $params['number'], $params['sku_id'], 1, $cart_type,$params['salesman_id'], $params['extra_attr_ids']);

        return $this->success([
            "item" => Util::lang("加入购物车成功"),
            'flow_type' => $cart_type
        ]);
    }
}
