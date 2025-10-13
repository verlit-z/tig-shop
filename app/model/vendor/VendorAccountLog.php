<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class VendorAccountLog extends Model
{
    protected $pk = 'vendor_account_log_id';
    protected $table = 'vendor_account_log';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;


}
