<?php

namespace app\model\product;

use think\Model;

class ECardGroup extends Model
{
    protected $pk = 'group_id';
    protected $table = 'e_card_group';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = 'up_time';
}