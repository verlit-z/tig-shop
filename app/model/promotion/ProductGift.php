<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 商品规格
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\promotion;

use app\model\product\Product;
use app\model\product\ProductSku;
use think\Model;

class ProductGift extends Model
{
    protected $pk = 'gift_id';
    protected $table = 'product_gift';

    protected $append = ['product_info','sku_info'];

    public function getProductInfoAttr()
    {
        return Product::query()->where('product_id', $this->product_id)->field([
            'product_name',
            'product_price',
            'pic_thumb',
            'product_sn',
            'product_type',
            'shop_id',
            'product_id'
        ])->find();
    }

    public function getSkuInfoAttr()
    {
        return ProductSku::query()->where('sku_id',$this->sku_id)->field(['sku_data','sku_sn','sku_price'])->find();
    }
}
