<?php

namespace app\model\salesman;


use app\model\BaseModel;
use app\model\finance\RefundLog;
use utils\Time;

class SalesmanOrder extends BaseModel
{
    protected $pk = 'salesman_order_id';
    protected $table = 'salesman_order';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';

    protected $json = ['salesman_product_data', 'salesman_settlement_data'];

    protected $jsonAssoc = true;

    protected $append = [
        'status_text'
    ];

    public function getSalesmanProductDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setSalesmanProductDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getSalesmanSettlementDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setSalesmanSettlementDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }


    public function userOrder()
    {
        return $this->hasOne(\app\model\order\Order::class, 'order_id', 'order_id');
    }

    public function userOrderItem()
    {
        return $this->hasOne(\app\model\order\OrderItem::class, 'item_id', 'item_id');
    }

    public function userOrderRefund()
    {
        return $this->hasMany(RefundLog::class, 'order_id', 'order_id');
    }

    // 分销员
    public function salesman()
    {
        return $this->hasOne(Salesman::class, 'salesman_id', 'salesman_id');
    }

    // 获取订单信息
    public function orderUserInfo()
    {
        return $this->hasOne(\app\model\order\Order::class, 'order_id', 'order_id')
            ->with('user')
            ->field(['order_id','order_sn','user_id','total_amount','add_time','pay_time','order_status','order_source']);
    }


    public function getStatusTextAttr($value, $data)
    {
        if ($data['status'] == 0) {
            return '待结算';
        } else {
            return '已结算';
        }
    }

    // 结算时间
    public function getSettlementTimeAttr($value)
    {
        if (!empty($value)) {
            return Time::format($value);
        }
        return "";
    }

    // 时间检索
    public function scopeAddTime($query, $start, $end)
    {
        if (!empty($start) && !empty($end)) {
            $start = Time::toTime($start);
            $end = Time::toTime($end) + 86400;
            return $query->whereBetweenTime($this->table . '.add_time', $start, $end);
        }
        return $query;
    }

}