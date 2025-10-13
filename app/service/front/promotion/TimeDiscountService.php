<?php

namespace app\service\front\promotion;

use app\model\promotion\TimeDiscountItem;
use app\service\common\BaseService;

/**
 * 营销服务
 */
class TimeDiscountService extends BaseService
{

    /**
     * 批量商品重新按活动计算价格
     * @param array $productList
     * @return array
     */
    public function parsePrice(array $products, mixed $timeDiscount): array
    {
        $result = [];
        $timeDiscount['item'] = TimeDiscountItem::where('discount_id', $timeDiscount['discount_id'])->select();
        foreach ($products['list'] as $key => $product) {
            foreach ($timeDiscount['item'] as $item) {
                if (in_array($product['sku_id'], $item['sku_ids'])) {
                    $result['carts'][$product['cart_id']]['price'] = $this->getTimeDiscountPrice($product, $item);
                }
            }
            if (isset($result['carts'][$product['cart_id']]['price'])) {
                $result['promotion'] = $timeDiscount;
                $result['promotion']['amount'] = ($product['price'] - $result['carts'][$product['cart_id']]['price']) * $product['quantity'];
                $result['promotion']['amount'] = max($result['promotion']['amount'], 0);
            }

        }
        return $result;
    }

    /**
     * 计算单个商品的折扣价格
     * @param $product
     * @param $timeDiscountItem
     * @return float|int
     */
    public function getTimeDiscountPrice($product, $timeDiscountItem): float|int
    {
        $price = $product['price'];
        if (in_array($product['sku_id'], $timeDiscountItem['sku_ids'])) {
            if ($timeDiscountItem['discount_type'] == 3) {
                $price = $timeDiscountItem['value'];
            } elseif ($timeDiscountItem['discount_type'] == 2) {
                $price = ($product['price'] - $timeDiscountItem['value']);
            } elseif ($timeDiscountItem['discount_type'] == 1) {
                $price = ($product['price'] * $timeDiscountItem['value'] / 10);
            }
        }
        return $price;
    }
}