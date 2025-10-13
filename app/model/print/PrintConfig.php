<?php

namespace app\model\print;

use app\model\BaseModel;

class PrintConfig extends BaseModel
{
    protected $pk = 'id';
    protected $table = 'print_config';
    protected $updateTime = "update_time";
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;
    protected $json = ["template"];
    protected $jsonAssoc = true;


}