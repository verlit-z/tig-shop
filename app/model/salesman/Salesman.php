<?php

namespace app\model\salesman;


use app\model\BaseModel;
use app\model\merchant\Shop;
use app\model\user\User;

class Salesman extends BaseModel
{
    protected $pk = 'salesman_id';
    protected $table = 'salesman';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';


    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

    // 基础用户信息
    public function baseUserInfo()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->field([
            "mobile",
            "username",
            'nickname',
            'avatar',
            'user_id',
            'distribution_register_time'
        ]);
    }

    // 关联分销订单
    public function salesmanOrderInfo()
    {
        return $this->hasMany(SalesmanOrder::class, 'salesman_id', 'salesman_id')
            ->field(['salesman_order_id','order_id','salesman_id','amount','status','item_id','order_amount','add_time']);

    }

    // 分组信息
    public function groupInfo()
    {
        return $this->hasOne(Group::class, 'group_id', 'group_id')->field([
            "group_id","group_name"
        ]);
    }

    // 上级分销员信息
    public function pidUserInfo()
    {
        return $this->hasOne(salesman::class, 'salesman_id', 'pid')
            ->with(['base_user_info'])->field(['salesman_id','user_id']);
    }

    // 关联订单信息
    public function orderTotalCommission()
    {
        return $this->hasMany(SalesmanOrder::class, 'salesman_id', 'salesman_id');
    }

    // 关联店铺
    public function shopInfo()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id')->field([
            'shop_id','shop_title','status'
        ]);
    }

    // 分销员客户
    public function customer()
    {
        return $this->hasMany(SalesmanCustomer::class, 'salesman_id', 'salesman_id');
    }

    // 分销已结算累计佣金
    public function getTotalCommissionAttr($value,$data)
    {
        if (!empty($data['salesman_id'])) {
            return SalesmanOrder::where(['salesman_id' => $data['salesman_id'],'status' => 1])->sum('amount');
        }
        return 0;
    }

    // 分销累计客户数
    public function getTotalCustomerAttr($value,$data)
    {
        if (!empty($data['salesman_id'])) {
            return SalesmanCustomer::where('salesman_id',$data['salesman_id'])->group('user_id')->count();
        }
        return 0;
    }

    // 分销累计邀请数
    public function getTotalInviteAttr($value,$data)
    {
        if (!empty($data['salesman_id'])) {
            return Salesman::where('pid',$data['salesman_id'])->count();
        }
        return 0;
    }







}