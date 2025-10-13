<?php

namespace app\model\user;

use app\model\BaseModel;

class UserRankConfig extends BaseModel
{
    protected $pk = 'id';
    protected $table = 'user_rank_config';
    protected $json = ['data'];
    protected $jsonAssoc = true;

    public function getDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}