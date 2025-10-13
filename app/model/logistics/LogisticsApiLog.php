<?php

namespace app\model\logistics;

use think\Model;

class LogisticsApiLog extends Model
{
    protected $pk = 'id';
    protected $table = 'logistics_api_log';
    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = true;
}