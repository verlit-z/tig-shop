<?php

namespace app\model\print;

use app\model\BaseModel;

class Printer extends BaseModel
{
    protected $pk = 'print_id';
    protected $table = 'print';
    protected $createTime = "add_time";
    protected $updateTime = "update_time";
    protected $autoWriteTimestamp = true;


}