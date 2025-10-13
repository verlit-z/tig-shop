<?php

namespace app\model\salesman;

use app\model\BaseModel;
use app\model\product\Product;
use utils\Time;

class SalesmanMaterial extends BaseModel
{
    protected $pk = 'id';
    protected $table = 'salesman_material';
    protected $json = ['pics'];
    protected $jsonAssoc = true;

    const STATUS_NOT_START = 0;
    const STATUS_SHOWING = 1;
    const STATUS_END = 2;
    const STATUS_MAP = [
        self::STATUS_NOT_START => '未开始',
        self::STATUS_SHOWING => '展示中',
        self::STATUS_END => '已失效',
    ];

    public function getPicsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setPicsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getStatusTextAttr($value, $data)
    {
        if (isset($data['start_time'], $data['end_time']) && !empty($data['start_time'])) {
            $now = Time::now();
            if ($now < $data['start_time']) {
                return self::STATUS_MAP[self::STATUS_NOT_START];
            } elseif ($now >= $data['start_time'] && $now <= $data['end_time'] || $data['end_time'] == 0) {
                return self::STATUS_MAP[self::STATUS_SHOWING];
            } else {
                return self::STATUS_MAP[self::STATUS_END];
            }
        }
        return "";
    }

    public function category()
    {
        return $this->hasOne(SalesmanMaterialCategory::class, 'category_id', 'category_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }


    // 时间转换
    public function setStartTimeAttr($value)
    {
        return Time::toTime($value);
    }

    public function getStartTimeAttr($value)
    {
        return Time::format($value);
    }

    public function setEndTimeAttr($value)
    {
        if (!empty($value)) {
            return Time::toTime($value);
        }
        return 0;
    }

    public function getEndTimeAttr($value)
    {
        if (!empty($value)) {
            return Time::format($value);
        }
        return "";
    }

    // 状态检索
    public function scopeStatus($query, $value)
    {
        if (!empty($value)) {
            switch ($value) {
                case self::STATUS_NOT_START:
                    // 未开始
                    $query = $query->where('start_time', '>', Time::now());
                    break;
                case self::STATUS_SHOWING:
                    // 展示中
                    $query = $query->where(function ($query) {
                        $query->where('start_time', '<=', Time::now())
                            ->where('end_time', '>=', Time::now())
                            ->whereOr('end_time', 0);
                    });
                    break;
                case self::STATUS_END:
                    // 已失效
                    $query = $query->where('end_time', '<', Time::now())->where('end_time', '>', 0);
                    break;
            }
        }
        return $query;
    }
}
