<?php

namespace app\model\salesman;


use app\model\BaseModel;

class Group extends BaseModel
{
    protected $pk = 'group_id';
    protected $table = 'salesman_group';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';


}