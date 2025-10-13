<?php

namespace app\model\vendor;

use think\Model;

class VendorProductSkuAttr extends Model
{
    protected $pk = 'id';
    protected $table = 'vendor_product_sku_attr';
    protected $autoWriteTimestamp = true;
}