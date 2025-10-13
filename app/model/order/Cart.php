<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 购物车
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use app\model\merchant\Shop;
use app\model\product\Product;
use app\model\product\ProductSku;
use think\Model;

class Cart extends Model
{
    protected $pk = 'cart_id';
    protected $table = 'cart';
    protected $json = ['sku_data', 'extra_sku_data'];
    protected $jsonAssoc = true;
    const TYPE_NORMAL = 1; //普通商品
    const TYPE_PIN = 2; //拼团商品
    const TYPE_EXCHANGE = 3; //兑换商品
    const TYPE_GIFT = 4;
    const TYPE_BARGAIN = 5;
    const TYPE_VIRTUAL = 6;
    const TYPE_PAID = 7;
    const TYPE_CARD = 8;


    public function getSkuDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
    public function setSkuDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getExtraSkuDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setExtraSkuDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }


    const TYPE_MAP = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_PIN => '拼团商品',
        self::TYPE_EXCHANGE => '兑换商品',
        self::TYPE_GIFT => '赠品',
        self::TYPE_BARGAIN => '砍一砍商品',
        self::TYPE_VIRTUAL => '虚拟商品',
        self::TYPE_PAID => '付费商品',
        self::TYPE_CARD => '卡密商品'
    ];

    // 关联 shop 表
    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id')->field(['shop_id', 'shop_title', 'shop_logo']);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id')->bind([
            'product_weight',
            'shipping_tpl_id',
            'free_shipping',
            'product_status',
            'product_name',
            'product_price',
            'category_id',
            'brand_id',
            'product_stock',
            'card_group_id',
            'virtual_sample',
            'suppliers_id'
        ]);
    }

    public function sku()
    {
        return $this->hasOne(ProductSku::class, 'sku_id', 'sku_id');
    }
}
