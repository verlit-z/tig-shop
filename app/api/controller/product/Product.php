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

namespace app\api\controller\product;

use app\api\IndexBaseController;
use app\service\admin\product\ProductDetailService;
use app\service\admin\product\ProductService;
use app\service\admin\product\ProductSkuService;
use app\service\admin\promotion\PointsExchangeService;
use app\service\admin\user\FeedbackService;
use app\service\admin\user\UserCouponService;
use app\service\admin\user\UserInfoService;
use app\service\front\promotion\CouponService;
use app\service\front\promotion\PromotionService;
use exceptions\ApiException;
use think\App;
use think\Response;
use utils\Util;

/**
 * 商品控制器
 */
class Product extends IndexBaseController
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
     * 商品信息
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(): \think\Response
    {
        // 此处id有可能传的是sn
        $product_id = $this->request->all('id', 0);
        $sku_id = $this->request->all('sku_id', 0);
        $goods_sn = $this->request->all('sn', '');
        if (!empty($goods_sn)) {
            [$product_id, $sku_id] = app(ProductService::class)->getProductKeyBySn($goods_sn);
        }

        $productDetailService = new ProductDetailService($product_id);
        $product = $productDetailService->getDetail();
        if ($product['is_delete'] == 1) {
            return $this->error(Util::lang('商品不存在'));
        }
        // 添加浏览记录
        if (request()->userId) {
            $userInfoService = new UserInfoService(request()->userId);
            $userInfoService->historyProductRecord($product_id);
        }
        $picList = $productDetailService->getPicList();
        $descArr = $productDetailService->getDescArr();
        if (!empty(request()->header('X-Locale-Code'))) {
            if (!empty($picList)) {
                foreach ($picList as &$pic) {
                    $pic['pic_url'] = Util::lang($pic['pic_url'], '', [], 7);
                }
            }
            if (!empty($descArr)) {
                foreach ($descArr as &$desc) {
                    if ($desc['type'] == 'pic' && !empty($desc['pic'])) {
                        $desc['pic'] = Util::lang($desc['pic'], '', [], 7);
                    }
                }
            }
        }
        return $this->success([
            'item' => $product,
            'desc_arr' => $descArr,
            'sku_list' => $productDetailService->getSkuList(),
            'pic_list' => $picList,
            'video_list' => $productDetailService->getVideoList(),
            'attr_list' => $productDetailService->getAttrList(),
            'rank_detail' => $productDetailService->getProductCommentRankDetail(),
            'seckill_detail' => $productDetailService->getSeckillInfo(),
            'service_list' => $productDetailService->getServiceList(),
            'checked_value' => $productDetailService->getSelectValue($sku_id),
            'consultation_total' => $productDetailService->getConsultationCount(),
        ]);
    }

    public function getComment(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $productDetailService = new ProductDetailService($id);
        return $this->success($productDetailService->getProductCommentDetail());
    }

    public function getCommentList(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $filter = $this->request->only([
            'id' => $id,
            'type/d' => 1,
            'page/d' => 1,
        ], 'get');
        if ($filter['type'] == 5) {
            $filter['is_showed'] = 1;
        }
        $productDetailService = new ProductDetailService($id);
        return $this->success([
            'records' => $productDetailService->getProductCommentList($filter),
            'total' => $productDetailService->getProductCommentCount($filter),
        ]);
    }

    /**
     * 获取商品咨询列表
     * @return \think\Response
     */
    public function getFeedbackList(): \think\Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'product_id/d' => 0,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');
        if (empty($filter['product_id'])) {
            return $this->error(Util::lang('请选择商品'));
        }
        $result = app(FeedbackService::class)->orderInquiryList($filter);
		$count = app(FeedbackService::class)->getFilterCount($filter);
        return $this->success([
            'records' => $result,
            'total' => $count,
        ]);
    }

    /**
     * 获取商品信息
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductAvailability(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $sku_id = $this->request->all('sku_id/d', 0);
        $is_exchange = $this->request->all('is_exchange/d', 0);
        //商品附加属性
        $extra_attr_ids = $this->request->all('extra_attr_ids', '');
        $productDetailService = new ProductDetailService($id);
        $result = $productDetailService->getProductSkuDetail($sku_id, $is_exchange, $extra_attr_ids);
        if ($is_exchange){
            $exchange_info = app(PointsExchangeService::class)->getInfoByProductId($result['product_id'],$sku_id);
            if (isset($exchange_info['points_deducted_amount'])){
                $result['price'] -= $exchange_info['points_deducted_amount'];
                $result['price'] = Util::number_format_convert(max($result['price'], 0));
            }
        }
        if(!empty($result['promotion'])) {
            foreach ($result['promotion'] as &$promotion) {
                $promotion['end_time'] = $promotion['end_time'] ? date('Y-m-d H:i:s', $promotion['end_time']) : '';
                $promotion['start_time'] = $promotion['start_time'] ? date('Y-m-d H:i:s', $promotion['start_time']) : '';
            }
        }

        return $this->success([
            'origin_price' => $result['origin_price'],
            'price' => $result['price'],
            'stock' => $result['stock'],
            'promotion' => $result['promotion'],
        ]);
    }


    public function getBatchProductAvailability()
    {
        $sku_ids = $this->request->all('skuIds', 0);
        $skuId = explode(',', $sku_ids);
        $result = [];
        foreach ($skuId as $sku_id) {
            $sku = app(ProductSkuService::class)->getDetail($sku_id);
            $productDetailService = new ProductDetailService($sku['product_id']);
            $productAvailability = $productDetailService->getProductSkuDetail($sku_id, 0, '');
            $skuResult = [
                'origin_price' => $productAvailability['origin_price'],
                'price' => $productAvailability['price'],
                'stock' => $productAvailability['stock'],
                'promotion' => $productAvailability['promotion'],
            ];
            $result[$sku_id] = $skuResult;
        }
        return $this->success($result);
    }

    /**
     * 商品优惠信息
     * @return Response
     */
    public function getProductsPromotion(): \think\Response
    {
        $products = $this->request->all('products', []);
        $params = $this->request->only([
            'products' => [],
        ], 'get');
        $products = $params['products'];
        $shopId = $this->request->all('shop_id', null);
        $from = $this->request->all('from', 'list');
        $promotion = app(PromotionService::class)->getProductsPromotion($products, $shopId, $from);
        return $this->success($promotion);
    }

    public function getProductAmount()
    {
        $id = $this->request->all('id/d', 0);
        $sku_item = $this->request->all('sku_item/a', [
        ]);
        $return = [
            'count' => 0,
            'total' => 0,
        ];
        foreach ($sku_item as $item) {
            $itemData = (new ProductDetailService($id))->getProductSkuDetail($item['sku_id'], 1);
            $return['count'] += $item['num'];
            $return['total'] = bcadd(bcmul($item['num'], $itemData['price'], 2), $return['total'], 2);
        }
        return $this->success($return);
    }

    /**
     * 批量获取商品价格
     */
    public function getPriceInBatches()
    {
        $products = input('products', []);
        $result = [];
        foreach ($products as $item) {
            $productDetailService = new ProductDetailService($item['productId']);
            $productAvailability = $productDetailService->getProductSkuDetail($item['skuId'], 0, '');
            $result[] = [
                'origin_price' => $productAvailability['origin_price'],
                'price' => $productAvailability['price'],
                'stock' => $productAvailability['stock'],
                'promotion' => $productAvailability['promotion'],
                'sku_id' => $item['skuId'],
                'product_id' => $item['productId'],
            ];
        }
        return $this->success($result);
    }

    /**
     * 商品列表
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'is_show/d' => -1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'product_id',
            'sort_order' => 'desc',
            'product_id/d' => 0,
            'is_delete/d' => -1,
            'category_id/d' => 0,
            'brand_id/d' => 0,
            'product_group_id' => 0,
            'ids' => null,
            'shop_id/d' => -2, // 店铺id
            'intro_type' => '', // 商品类型
            'coupon_id' => 0,
            'shop_category_id' => -1,
            'with_cart_sum' => 0
        ], 'get');
        $filter['is_delete'] = 0;
        $filter['product_status'] = 1;
        if(isset($filter['ids']) && !empty($filter['ids'])) {
            $raw = !is_array($filter['ids']) ? $filter['ids'] : implode(',', $filter['ids']);
            $filter['sort_field_raw'] = "field(product_id," . $raw . ")";
        }
        $filterResult = app(ProductService::class)->getFilterResult($filter);
        $total = app(ProductService::class)->getFilterCount($filter);
        $waiting_checked_count = app(ProductService::class)->getWaitingCheckedCount();

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
            'waiting_checked_count' => $waiting_checked_count,
        ]);
    }

    /**
     * 商品优惠劵
     * @return \think\Response
     */
    public function getCouponList(): \think\Response
    {
        $filter = $this->request->only([
            'id/d' => 0,
        ], 'get');
        $product = app(ProductService::class)->getDetail($filter['id']);

        $coupon = app(CouponService::class)->getProductCouponList($product['product_id'],
            $product['shop_id'], request()->userId);
        $userCoupon = app(UserCouponService::class)->getFilterResult([
            'size' => 10000,
            'page' => 1,
            'used_time' => 0,
            'user_id' => request()->userId
        ]);
        $userCouponArr = [];
        if (!empty($userCoupon['list']) && is_array($userCoupon['list'])) {
            foreach ($userCoupon['list'] as $item) {
                $userCouponArr[] = $item['coupon_id'];
            }
        }
        $exist_coupon = [];
        foreach ($coupon as $k => $c) {
            if (in_array($c['coupon_id'], $userCouponArr)) {
                $c['is_receive'] = 1;
                $exist_coupon[] = $c;
                unset($coupon[$k]);
            } else {
                $coupon[$k]['is_receive'] = 0;
            }

        }
        $coupon = array_merge($coupon, $exist_coupon);
        return $this->success($coupon);
    }

    /**
     * 判断商品是否被收藏
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function isCollect(): \think\Response
    {
        $id = $this->request->all('id/d', "");
        $productDetailService = new ProductDetailService($id);
        $result = $productDetailService->getIsCollect();
        return $this->success($result);
    }

    /**
     * 获取相关商品
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductRelated():Response
    {
        $id = $this->request->all('product_id/d', "");
        $productDetailService = new ProductDetailService($id);
        $result = $productDetailService->getRelatedList();
        return $this->success($result);
    }


}
