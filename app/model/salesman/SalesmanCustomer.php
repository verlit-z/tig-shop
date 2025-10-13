<?php

namespace app\model\salesman;


use app\model\BaseModel;
use app\model\user\User;

class SalesmanCustomer extends BaseModel
{
    protected $pk = 'salesman_customer_id';
    protected $table = 'salesman_customer';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';


    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

}