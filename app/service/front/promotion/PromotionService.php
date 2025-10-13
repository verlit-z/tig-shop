<?php

namespace app\service\front\promotion;

use app\model\promotion\ProductGift;
use app\model\promotion\Promotion;
use app\service\common\BaseService;
use utils\Time;

/**
 * 营销服务
 */
class PromotionService extends BaseService
{

    public $promotion = null;

    /**
     * 获得商品的优惠信息,列表使用简化优惠版本
     * @return array
     */
    public function getProductsPromotion(array $products, mixed $shopId, string $promotionFrom): array
    {
        $productPromotion = [];
        //获得所有进行中的活动
        $promotions = $this->getAllAvailablePromotion($shopId);

        foreach ($products as $key => $product) {
            if ($promotionFrom == 'list') {
                $key = $product['product_id'];
            } elseif ($promotionFrom == 'cart') {
                $key = $product['cart_id'];
            } elseif ($promotionFrom == 'detail') {
                $key = $product['sku_id'];
            }
            $product['shop_id'] = $shopId;
            $productPromotion[$key] = $product;
            foreach ($promotions as $k => $promotion) {
                //不满足条件的去除
                if ($promotion->checkPromotionIsAvailable($product)) {
                    $promotion['data'] = $promotion->realPromotion;
                    if ($promotionFrom == 'list') {
                        if ($promotion['is_delete'] == 1) {
                            continue;
                        }
                    }
                    $productPromotion[$key]['activity_info'][] = $promotion;
                    if ($promotionFrom == 'list' && $promotion->type != 6) {
                        break;
                    }

                    if ($promotion->type == 5) {
                        $gitData = $promotion['data']['promotion_type_data'];
                        foreach ($gitData as $index => $gift) {
                            $gitData[$index]['gift'] = ProductGift::where('gift_id',
                                $gift['giftId'])->find();
                            if (isset($gitData[$index]['gift']['skuInfo']['skuPrice'])) {
                                $gitData[$index]['gift']['product_info']['product_price'] = $gitData[$index]['gift']['skuInfo']['skuPrice'];
                            }
                        }
                        $promotion['data']['promotion_type_data'] = $gitData;
                    }

                }

            }
        }
        return $productPromotion;
    }


    /**
     * 获得各种活动的服务
     * c     * @return void
     */
    public function getRealPromotionService(int $type): BaseService
    {
        switch ($type) {
            case 1:
                return new SeckillService();
            case 2:
                return new CouponService();
            case 3:
            case 4:
            case 5:
                return new ProductPromotionService();
            case 6:
                return new TimeDiscountService();
        }
    }

    /**
     * 获得所有有效的活动
     * @return void
     */
    public function getAllAvailablePromotion(mixed $shopId = null)
    {
        $time = Time::now();
        $where = [
            ['start_time', '<=', $time],
            ['end_time', '>=', $time],
        ];

        $model = Promotion::where(function ($query) use ($where) {
            $query->where($where)->whereOr([
                ['start_time', '=', 0],
                ['end_time', '=', 0]
            ]);
        });
        if ($shopId !== null) {
            $model = $model->where('shop_id', $shopId);
        }
        $model = $model->where('is_available', 1);
        return $model->orderRaw('FIELD(`type`, 1,6,2,3,4,5)')->select();
    }




}