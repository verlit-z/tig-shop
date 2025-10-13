<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 商品规格
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

class TimeDiscount extends Model
{
    protected $pk = 'discount_id';
    protected $table = 'time_discount';

    protected $createTime = 'add_time';

    protected $autoWriteTimestamp = 'int';

    protected $append = ['status_name'];


    // 活动状态
    const PROMOTION_STATUS_ON = 1;
    const PROMOTION_STATUS_OFF = 2;
    const PROMOTION_STATUS_FORTHCOMING = 3;
    const PROMOTION_STATUS_NAME = [
        self::PROMOTION_STATUS_ON => '活动进行中',
        self::PROMOTION_STATUS_OFF => '活动已结束',
        self::PROMOTION_STATUS_FORTHCOMING => '活动未开始',
    ];

    public function getStatusNameAttr($value, $data)
    {
        if (!empty($data['start_time']) || !empty($data['end_time'])) {
            if (Time::now() < $data['start_time']) {
                return self::PROMOTION_STATUS_NAME[self::PROMOTION_STATUS_FORTHCOMING];
            } elseif (Time::now() > $data['end_time']) {
                return self::PROMOTION_STATUS_NAME[self::PROMOTION_STATUS_OFF];
            } else {
                return self::PROMOTION_STATUS_NAME[self::PROMOTION_STATUS_ON];
            }
        }
        return "--";
    }

    // 秒杀时间
    public function getStartTimeAttr($value)
    {
        return Time::format($value);
    }

    public function getEndTimeAttr($value)
    {
        return Time::format($value);
    }

    public function item()
    {
        return $this->hasMany(TimeDiscountItem::class, 'discount_id', 'discount_id');
    }

    public function promotion()
    {
        return $this->morphOne(Promotion::class, 'type');
    }
}
