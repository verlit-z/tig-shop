<?php

namespace app\model\user;

use app\model\BaseModel;

class UserRankLog extends BaseModel
{
    protected $pk = 'id';
    protected $table = 'user_rank_log';
    protected $updateTime = "change_time";
    protected $autoWriteTimestamp = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->field(['user_id',"username"]);
    }
}