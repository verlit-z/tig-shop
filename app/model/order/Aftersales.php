<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 退换货
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use app\model\finance\RefundApply;
use think\Model;
use utils\Time;
use utils\Util;

class Aftersales extends Model
{
    protected $pk = 'aftersale_id';
    protected $table = 'aftersales';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;
    protected $json = ['pics'];
    protected $jsonAssoc = true;

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

    // 关联订单商品信息
    public function aftersalesItems()
    {
        return $this->hasMany(AftersalesItem::class, 'aftersale_id', 'aftersale_id');
    }
    // 关联售后日志
    public function aftersalesLog()
    {
        return $this->hasMany(AftersalesLog::class, 'aftersale_id', 'aftersale_id')->order('log_id desc');
    }

    public function orderSn()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id')->bind(['order_sn']);
    }

    public function orders()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    public function refund()
    {
        return $this->hasOne(RefundApply::class, 'aftersale_id', 'aftersale_id');
    }

    // 申请类型
    const AFTERSALES_TYPE_RETURN = 1;
    const AFTERSALES_TYPE_EXCHANGE = 2;
    const AFTERSALES_TYPE_MAINTENANCE = 3;
    const AFTERSALES_TYPE_OTHER = 4;
    const AFTERSALES_TYPE_PAYRETURN = 2;
    const AFTERSALES_TYPE_NAME = [
        self::AFTERSALES_TYPE_PAYRETURN => '仅退款',
        self::AFTERSALES_TYPE_RETURN => '退货/退款',
//        self::AFTERSALES_TYPE_EXCHANGE => '换货',
//        self::AFTERSALES_TYPE_MAINTENANCE => '维修',
//        self::AFTERSALES_TYPE_OTHER => '其他',
    ];

    const REFUSE_REASON = [
        '已经超过七天无理由退货时限',
        '商品没有问题，买家未举证',
        '商品没有问题，买家举证无效',
        '已协商完毕不退货',
    ];

    // 状态
    const STATUS_IN_REVIEW = 1;
    const STATUS_APPROVED_FOR_PROCESSING = 2;
    const STATUS_REFUSE = 3;
    const STATUS_SEND_BACK = 4;
    const STATUS_RETURNED = 5;
    const STATUS_COMPLETE = 6;
    const STATUS_CANCEL = 7;
    const STATUS_WAIT_FOR_SUPPLIER_AUDIT = 21;

    const STATUS_WAIT_FOR_SUPPLIER_RECEIPT = 22;



    const STATUS_NAME = [
        self::STATUS_IN_REVIEW => '审核处理中',
        self::STATUS_APPROVED_FOR_PROCESSING => '审核通过',
        self::STATUS_REFUSE => '审核未通过',
        self::STATUS_SEND_BACK => '待用户回寄',
        self::STATUS_RETURNED => '待商家收货',
        self::STATUS_COMPLETE => '已完成',
        self::STATUS_CANCEL => '已取消',
        self::STATUS_WAIT_FOR_SUPPLIER_AUDIT => '待供应商审核',
        self::STATUS_WAIT_FOR_SUPPLIER_RECEIPT => '待供应商收货',
    ];


    /**
     * 代表该售后有效的状态
     */
    const VALID_STATUS = [
        self::STATUS_IN_REVIEW,
        self::STATUS_APPROVED_FOR_PROCESSING,
        self::STATUS_REFUSE,
        self::STATUS_SEND_BACK,
        self::STATUS_RETURNED,
        self::STATUS_COMPLETE,
        self::STATUS_WAIT_FOR_SUPPLIER_AUDIT,
        self::STATUS_WAIT_FOR_SUPPLIER_RECEIPT,
    ];
    /**
     * 代表该售后进行中状态
     */
    const PROGRESSING_STATUS = [
        self::STATUS_IN_REVIEW,
        self::STATUS_APPROVED_FOR_PROCESSING,
        self::STATUS_SEND_BACK,
        self::STATUS_RETURNED,
        self::STATUS_WAIT_FOR_SUPPLIER_AUDIT,
        self::STATUS_WAIT_FOR_SUPPLIER_RECEIPT,
    ];

    const AFTERSALES_REASON = [
        '多拍/拍错/不喜欢',
        '未按约定时间发货',
        '协商一致退款',
        '地址/电话填错了',
        '其他',
    ];

    // 状态名称
    public function getStatusNameAttr($value, $data): string
    {
        return Util::lang(self::STATUS_NAME[$data['status']]) ?? '';
    }

    // 申请类型名称
    public function getAftersalesTypeNameAttr($value, $data): string
    {
        return Util::lang(self::AFTERSALES_TYPE_NAME[$data['aftersale_type']]) ?? '';
    }

    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }

    /**
     * 检验是否能取消申请
     * @return bool
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_IN_REVIEW,
        ]);
    }

    // 关键词检索 -- 订单号
    public function scopeKeywords($query, $value)
    {
        if (!empty($value)) {
            return $query->hasWhere('orders', function ($query) use ($value) {
                    $query->where('order_sn', 'like', "%$value%");
                });
        }
        return $query;
    }

    // 时间格式转换
    public function getAuditTimeAttr($value): string
    {
        return Time::format($value);
    }

    public function getDealTimeAttr($value): string
    {
        return Time::format($value);
    }

    public function getFinalTimeAttr($value): string
    {
        return Time::format($value);
    }

}
