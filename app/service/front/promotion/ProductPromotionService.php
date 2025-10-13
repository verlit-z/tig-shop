<?php

namespace app\service\front\promotion;

use app\model\promotion\ProductGift;
use app\service\common\BaseService;

class ProductPromotionService extends BaseService
{
    /**
     * 批量商品重新按活动计算价格
     * @param array $productList
     * @return array
     */
    public function parsePrice(array $products, mixed $productPromotion): array
    {
        //先看总价是否满足优惠券要求
        $result = [];
        // 比较是按元/件 满减
        $compare_data = $productPromotion['unit'] == 1 ? $products['total_subtotal'] : $products['total_quantity'];
        if ($productPromotion['min_order_amount'] <= $compare_data) {
            //满足要求则计算总的优惠并分摊到每个商品上
            $promotion = [];//具体的优惠信息是哪些
            $result['promotion'] = $productPromotion;
            foreach ($productPromotion['promotion_type_data'] as $promotionData) {
                if ($promotionData['minAmount'] <= $compare_data) {
                    $promotion = $promotionData;
                }
            }
            if (empty($promotion)) {
                return [];
            }
            $priceAmount = 0;
            foreach ($products['list'] as $product) {
                $priceAmount = bcadd($priceAmount, $product['price'] * $product['quantity'], 2);
            }
            //如果是满减满折则计算单价
            if (in_array($productPromotion['promotion_type'], [1, 2])) {
                if ($productPromotion['promotion_type'] == 1) {
                    $totalDiscount = $productPromotion['rules_type'] == 1 ? $promotion['reduce'] : floor($compare_data / $promotion['min_amount']) * $promotion['reduce'];

                    //按比例看总优惠金额是多少
                    if ($totalDiscount >= $priceAmount) {
                        $result['promotion']['amount'] = $priceAmount;
                        //最大优惠比例100，也就是0折
                        $discountProportion = 0;
                    } else {
                        $result['promotion']['amount'] = $totalDiscount;
                        $discountProportion = ($priceAmount - $totalDiscount) / $priceAmount;
                    }
                } else {
                    // 满折 -- 满折没有循环优惠
                    $discountProportion = $promotion['reduce'] / 10;
                    // 优惠价格

                    $result['promotion']['amount'] = $products['total_subtotal'] * (1 - $discountProportion);
                    if ($result['promotion']['amount'] >= $priceAmount) {
                        $result['promotion']['amount'] = $priceAmount;
                    }
                }

                //总的优惠价格和比例出来了，平摊到每个商品价格上
                foreach ($products['list'] as $product) {
                    $result['carts'][$product['cart_id']]['price'] = $discountProportion * $product['price'];
                }
            } else {
                $result['promotion']['amount'] = 0;

                //如果是满赠返回满赠的信息
                $gift = ProductGift::where('gift_id', $promotion['giftId'])->where('gift_stock', '>', 0)->find();
                if ($gift) {
                    $promotion['product_id'] = $gift['product_id'];
                    $promotion['sku_id'] = $gift['sku_id'];
                    $promotion['product_name'] = $gift['product_info']['product_name'];
                    $promotion['product_sn'] = $gift['product_info']['product_sn'];
                    $promotion['pic_thumb'] = $gift['product_info']['pic_thumb'];
                    $promotion['type'] = 4;
                    $promotion['product_type'] = $gift['product_info']['product_type'];
                    $promotion['shop_id'] = $gift['product_info']['shop_id'];
                    $promotion['sku_data'] = isset($gift['sku_info']['sku_data']) ? $gift['sku_info']['sku_data'] : [];
                    $promotion['rules_type'] = $productPromotion['rules_type'];
//                    if ($productPromotion['rules_type'] == 0) {
//                        $promotion['num'] = $promotion['num'] * floor($products['total_subtotal'] / $promotion['min_amount']);
//                    }
                    $result['gift'] = $promotion;
                }
                $result['carts'] = [];
            }
            if (isset($result['promotion']['amount'])) {
                $result['promotion']['amount'] = max($result['promotion']['amount'], 0);
            }
        }
        //不满足则返回空数组
        return $result;
    }

}