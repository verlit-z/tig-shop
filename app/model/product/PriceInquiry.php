<?php

namespace app\model\product;

use app\model\merchant\Shop;
use think\Model;

class PriceInquiry extends Model
{
    protected $pk = 'id';
    protected $table = 'price_inquiry';
    protected $createTime = 'create_time';
    protected $autoWriteTimestamp = true;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id')
            ->field(['product_id','product_name','product_sn', 'pic_thumb']);
    }

    public function shopInfo()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id')
            ->field(['shop_id','shop_title']);
    }
}