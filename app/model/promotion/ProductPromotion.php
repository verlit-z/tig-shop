<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 优惠活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\promotion;

use think\Model;
use utils\Time;

class ProductPromotion extends Model
{
    protected $pk = 'promotion_id';
    protected $table = 'product_promotion';
    protected $json = ['limit_user_rank', 'range_data', 'promotion_type_data'];
    protected $jsonAssoc = true;

    protected $append = [
        'promotion_desc'
    ];

    // 优惠活动类型
    const PROMOTION_TYPE_FULL_REDUCE = 1;
    const PROMOTION_TYPE_FULL_DISCOUNT = 2;
    const PROMOTION_TYPE_FULL_REDUCE_NAME = 3;

    // 优惠活动类型映射
    protected const PROMOTION_TYPE_MAP = [
        self::PROMOTION_TYPE_FULL_REDUCE => '满减',
        self::PROMOTION_TYPE_FULL_DISCOUNT => '满折',
        self::PROMOTION_TYPE_FULL_REDUCE_NAME => '赠品',
    ];

    // 优惠活动分组
    const PROMOTION_GROUP = [
        self::PROMOTION_TYPE_FULL_REDUCE,
        self::PROMOTION_TYPE_FULL_DISCOUNT,
    ];

    // 优惠活动状态
    const PROMOTION_STATUS_ON = 1;
    const PROMOTION_STATUS_OFF = 2;
    const PROMOTION_STATUS_FORTHCOMING = 3;
    const PROMOTION_STATUS_NAME = [
        self::PROMOTION_STATUS_ON => '活动进行中',
        self::PROMOTION_STATUS_OFF => '活动已结束',
        self::PROMOTION_STATUS_FORTHCOMING => '活动未开始',
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

    public function getPromotionTypeDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setPromotionTypeDataAttr($value)
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

    public function setRangeDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    // 优惠活动描述
    public function getPromotionDescAttr($value, $data)
    {
        if (isset($data['promotion_id'])) {
            $prefix = ($data['rules_type']) ? '' : '每';
            $unit = $data["unit"] == 1 ? "元" : "件";
            if (is_array($data['promotion_type_data'])) {
                $data['promotion_type_data'] = camelCase($data['promotion_type_data']);
                foreach ($data['promotion_type_data'] as $k => $item) {
                    $desc[$k] = $item;
                    switch ($data['promotion_type']) {
                        case self::PROMOTION_TYPE_FULL_REDUCE:
                            $desc[$k]['desc'] = "{$prefix}满" . floatval($item['minAmount']) . $unit ."减" . floatval($item['reduce']) . "元";
                            break;
                        case self::PROMOTION_TYPE_FULL_DISCOUNT:
                            $desc[$k]['desc'] = "{$prefix}满{}" . floatval($item['minAmount']) . $unit ."打{$item['reduce']}折";
                            break;
                        case self::PROMOTION_TYPE_FULL_REDUCE_NAME:
                            $desc[$k]['desc'] = "{$prefix}满" . floatval($item['minAmount']) . "送赠品：{$item['giftName']}--数量{$item['num']}";
                            break;
                    }
                }
                return $desc;
            }
        }
        return [];
    }


    // 优惠活动类型名称
    public function getPromotionTypeNameAttr($value, $data): string
    {
        return self::PROMOTION_TYPE_MAP[$data['promotion_type']] ?? '';
    }

    // 结束时间
    public function getEndTimeAttr($value)
    {
        return Time::format($value);
    }

    //优惠活动开始时间
    public function getStartTimeAttr($value)
    {
        return Time::format($value);
    }

    //活动赠品
    public function getProductGiftAttr()
    {
        $typeData = $this->promotion_type_data;
        if(!empty($typeData))
        {
            $giftIds = array_unique(array_column($typeData, 'gift_id'));

            return ProductGift::query()->whereIn('gift_id', $giftIds)->select()->toArray();
        }
        return [];
    }

    // 活动状态
    public function getProductStatusAttr()
    {
        $end_time = $this->end_time;
        $start_time = $this->start_time;
        if (!empty($end_time) && !empty($start_time)) {
            if (time() < strtotime($start_time)) {
                $status = 3;
            } elseif (time() > strtotime($end_time)) {
                $status = 2;
            } else {
                $status = 1;
            }
            return self::PROMOTION_STATUS_NAME[$status] ?? '';
        }
        return "";
    }

    // 活动时间
    public function getProductTimeAttr()
    {
        $end_time = $this->end_time;
        $start_time = $this->start_time;
        if (!empty($end_time) && !empty($start_time)) {
            return [$start_time, $end_time];
        }
        return [];
    }

    // 活动状态检索
    public function scopeProductStatus($query, $status): void
    {
        switch ($status) {
            case self::PROMOTION_STATUS_ON:
                $query->where('start_time', '<=', time())->where('end_time', '>=', time());
                break;
            case self::PROMOTION_STATUS_OFF:
                $query->where('end_time', '<', time());
                break;
            case self::PROMOTION_STATUS_FORTHCOMING:
                $query->where('start_time', '>', time());
                break;
        }
    }

    public function promotion()
    {
        return $this->morphOne(Promotion::class, 'type');
    }
}
