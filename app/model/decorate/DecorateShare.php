<?php

namespace app\model\decorate;

use think\Model;

class DecorateShare extends Model
{
    protected $pk = 'share_id';
    protected $table = 'decorate_share';
    protected $autoWriteTimestamp = true;
}