<?php

namespace app\model\vendor;

use think\Model;

class VendorProductAuditLog extends Model
{
    protected $table = 'vendor_product_audit_log';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;

}