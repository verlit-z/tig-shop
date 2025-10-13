<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class VendorSettlementOrder extends Model
{
    protected $pk = 'vendor_settlement_order_id';
    protected $table = 'vendor_settlement_order';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

}
