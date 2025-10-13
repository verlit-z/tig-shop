<?php

namespace app\model\vendor;

use think\Model;

class VendorProductSkuStockLog extends Model
{
    protected $pk = 'id';
    protected $table = 'vendor_product_sku_stock_log';
    protected $autoWriteTimestamp = true;

    const OPERATION_TYPE_ADD = 1; //操作类型:增加
    const OPERATION_TYPE_SUB = 2;//操作类型:减少

    const BIZ_TYPE_ADD = 1;//业务类型:新增
    const BIZ_TYPE_UPDATE = 2;//业务类型:存编辑
}