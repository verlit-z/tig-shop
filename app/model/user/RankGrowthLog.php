<?php

namespace app\model\user;

use think\Model;

class RankGrowthLog extends Model
{
    protected $pk = 'id';
    protected $table = 'rank_growth_log';
    protected $createTime = "create_time";
    protected $autoWriteTimestamp = true;

    const GROWTH_TYPE_ORDER = 1;
    const GROWTH_TYPE_REFUND = 2;
    const GROWTH_TYPE_INFORMATION = 3;
    const GROWTH_TYPE_BIND_PHONE = 4;

    const GROWTH_TYPE_MAP = [
        self::GROWTH_TYPE_ORDER => '完成订单',
        self::GROWTH_TYPE_REFUND => '退款',
        self::GROWTH_TYPE_INFORMATION => '完善信息',
        self::GROWTH_TYPE_BIND_PHONE => '绑定手机号',
    ];
}