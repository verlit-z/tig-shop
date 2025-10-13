<?php

namespace app\model\order;

use think\Model;

class OrderConfig extends Model
{
    protected $pk = 'id';
    protected $table = 'order_config';
    protected $json = ['data'];
    protected $jsonAssoc = true;
}