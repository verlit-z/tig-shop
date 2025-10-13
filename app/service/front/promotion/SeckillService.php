<?php

namespace app\service\front\promotion;

use app\model\promotion\SeckillItem;
use app\service\common\BaseService;

class SeckillService extends BaseService
{
    /**
     * 批量商品重新按活动计算价格
     * @param array $products
     * @return array
     */
    public function parsePrice(array $products, mixed $seckill): array
    {
        $result = [];
        $seckill['amount'] = 0;
        foreach ($products['list'] as $cart_id => $product) {
            $item = SeckillItem::where([
                'seckill_id' => $seckill['seckill_id'],
                'sku_id' => $product['sku_id']
            ])->find();
//            $seckill['amount'] = bcadd($seckill['amount'],
//                ($product['origin_price'] - $item['seckill_price']) * $product['quantity'], 2);
//            $seckill['amount'] = max( $seckill['amount'],0);
            //查询确认是否满足活动，但不参与金额计算，同时也要返回活动标识给前端。
            $result[$cart_id]['price'] = $item['seckill_price'];
        }

        return ['carts' => $result, 'promotion' => $seckill];
    }
}