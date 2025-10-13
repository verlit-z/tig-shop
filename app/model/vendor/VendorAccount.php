<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class VendorAccount extends Model
{
    protected $pk = 'account_id';
    protected $table = 'vendor_account';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;


}
