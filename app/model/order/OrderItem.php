<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 订单商品
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use app\model\product\ECard;
use app\model\product\Product;
use app\model\product\ProductSku;
use think\Model;
use utils\Util;

class OrderItem extends Model
{
    protected $pk = 'item_id';
    protected $table = 'order_item';
    protected $json = ['sku_data', 'promotion_data', 'extra_sku_data'];
    protected $jsonAssoc = true;
    protected $hidden = ['promotion_data'];

    public function getPromotionDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setPromotionDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

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

    public function getProductNameAttr($value, $data)
    {
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value, '', [], 2);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    // 关联订单表
    public function orders()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }
    // 定义和商品表Product的关联关系，指定需要的字段
    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id')
            ->bind(['product_pic_thumb' => "pic_thumb", 'product_stock', "product_weight",'virtual_sample','paid_content','card_group_id']);
    }
    public function productSku()
    {
        return $this->hasOne(ProductSku::class, 'sku_id', 'sku_id')->bind(['sku_stock', 'sku_sn', "sku_value"]);
    }

    public function eCard()
    {
        return $this->hasMany(ECard::class, 'order_item_id', 'item_id')->field(['card_id','group_id','card_number','card_pwd','order_item_id']);
    }

    // 关联售后
    public function aftersalesItem()
    {
        return $this->hasMany(AftersalesItem::class, 'order_item_id', 'item_id');
    }

    // 商品名称 + 商品编号
    public function scopeKeyword($query, $value)
    {
        if (!empty($value)) {
            return $query->where('product_name|product_sn', 'like', "%{$value}%");
        }
        return $query;
    }
}
