<?php

namespace app\model\product;

use think\Model;

class ECard extends Model
{
    protected $pk = 'card_id';
    protected $table = 'e_card';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = 'up_time';
}