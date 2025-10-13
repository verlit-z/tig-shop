<?php
declare (strict_types = 1);

namespace app\model\vendor;

use think\Model;

/**
 * @mixin \think\Model
 */
class VendorShopBind extends Model
{
    protected $pk = 'id';
    protected $table = 'vendor_shop_bind';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

}
