<?php

namespace app\model\promotion;

use app\model\product\Product;
use think\Model;

class Promotion extends Model
{
    protected $pk = 'promotion_id';
    protected $table = 'promotion';

    protected $json = ['range_data', 'sku_ids'];

    protected $jsonAssoc = true;

    // 使用范围类型
    const TYPE_SECKILL = 1;
    const TYPE_COUPON = 2;
    const TYPE_PRODUCT_PROMOTION_1 = 3;
    const TYPE_PRODUCT_PROMOTION_2 = 4;
    const TYPE_PRODUCT_PROMOTION_3 = 5;
    const TYPE_PRODUCT_PROMOTION_4 = 6;

    const TYPE_MAP = [
        self::TYPE_SECKILL => '秒杀',
        self::TYPE_COUPON => '优惠券',
        self::TYPE_PRODUCT_PROMOTION_1 => '满减',
        self::TYPE_PRODUCT_PROMOTION_2 => '满折',
        self::TYPE_PRODUCT_PROMOTION_3 => '满赠',
        self::TYPE_PRODUCT_PROMOTION_4 => '限时折扣',
    ];

	// 活动范围
	const PROMOTION_RANGE_ALL = 0;
	const PROMOTION_RANGE_PRODUCT = 3;
	const PROMOTION_RANGE_EXCLUDE_PRODUCT = 4;
	const PROMOTION_RANGE_NAME = [
		self::PROMOTION_RANGE_ALL => '全场',
		self::PROMOTION_RANGE_PRODUCT => '指定商品',
		self::PROMOTION_RANGE_EXCLUDE_PRODUCT => '指定商品不参与',
	];

    public function getPromotionNameAttr($value, $data)
    {
        if (!$value) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = \utils\Util::lang($value, '', [], 10);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }


    public function setRangeDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getRangeDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getTimeTextAttr($value, $data)
    {
        if (empty($data['start_time']) && empty($data['end_time'])) {
            return "长期有效";
        } else {
            return date('Y-m-d H:i:s', $data['start_time']) . ' 至 ' . date('Y-m-d H:i:s', $data['end_time']);
        }
    }

    public function getTypeTextAttr($value, $data)
    {
        return isset(self::TYPE_MAP[$data['type']]) ? self::TYPE_MAP[$data['type']] : '';
    }

    /**
     * 真实活动对象
     * @return \think\model\relation\MorphTo
     */
    public function realPromotion()
    {
        return $this->morphTo(['type', 'relation_id'], [
            '1' => 'app\model\promotion\Seckill',
            '2' => 'app\model\promotion\Coupon',
            '3' => 'app\model\promotion\ProductPromotion',
            '4' => 'app\model\promotion\ProductPromotion',
            '5' => 'app\model\promotion\ProductPromotion',
            '6' => 'app\model\promotion\TimeDiscount',
        ]);
    }

    /**
     * 查询是否满足该活动
     * @param array $params
     * @return bool
     */
    public function checkPromotionIsAvailable(array $params): bool
    {

        if (!empty($params['product_id'])) {

            if ($this->range == 3 && !in_array($params['product_id'], $this->range_data)) {
                return false;
            } elseif ($this->range == 4 && in_array($params['product_id'], $this->range_data)) {
                return false;
            }
            if (empty($params['shop_id'])) {
                $params['shop_id'] = Product::where('product_id', $params['product_id'])->value('shop_id');
            }
        }
        if (!empty($params['sku_id'])) {
            if (!empty($this->sku_ids) && !in_array($params['sku_id'], $this->sku_ids)) {
                return false;
            }
        }
        if ($params['shop_id'] !== $this->shop_id) {

            return false;
        }
        return true;
    }
}