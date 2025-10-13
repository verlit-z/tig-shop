<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class Vendor extends Model
{
    protected $pk = 'vendor_id';
    protected $table = 'vendor';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

}
