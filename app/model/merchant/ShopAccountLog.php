<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 店铺
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\merchant;

use app\model\user\User;
use think\Model;
use utils\Time;

class ShopAccountLog extends Model
{
    protected $pk = 'shop_account_log_id';
    protected $table = 'shop_account_log';

    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = 'int';

    // 字段处理
    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id')->bind(['shop_title','un_settlement_order']);
    }

}
