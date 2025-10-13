<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class VendorWithdraw extends Model
{
    protected $pk = 'vendor_withdraw_log_id';
    protected $table = 'vendor_withdraw';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

}
