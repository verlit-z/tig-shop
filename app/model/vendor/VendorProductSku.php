<?php

namespace app\model\vendor;

use think\Model;

class VendorProductSku extends Model
{
    protected $pk = 'id';
    protected $table = 'vendor_product_sku';
    protected $autoWriteTimestamp = true;
    protected $json = ['sku_attr_json'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    const BIZ_TYPE_INSERT = 1; //插入

    const BIZ_TYPE_UPDATE = 2; //更新

    const BIZ_TYPE_ORDER_CREATE = 3; //下单

    const BIZ_TYPE_ORDER_CANCEL = 4; //取消订单
}