<?php

namespace app\model\vendor;

use app\model\product\Brand;
use app\model\product\Category;
use think\Model;

class VendorProduct extends Model
{
    protected $pk = 'id';
    protected $table = 'vendor_product';
    protected $autoWriteTimestamp = true;

    const PRODUCT_WAIT_AUDIT = 0;//待审核

    const PRODUCT_AUDIT_PASS = 1;//审核通过

    const PRODUCT_AUDIT_FAIL = 2;//审核失败

    const PRODUCT_STATUS_ON = 1; //在售

    const PRODUCT_STATUS_OFF = 0; //断供
    const PRODUCT_IS_RECYCLE = 1; //回收
    const PRODUCT_IS_NOT_RECYCLE = 0; //未回收


    public function skus()
    {
        return $this->hasMany(VendorProductSku::class, 'vendor_product_id', 'id');
    }

    public function galleries()
    {
        return $this->hasMany(VendorProductGallery::class, 'vendor_product_id', 'id')->order('sort_order ASC');
    }

   public function video()
   {
       return $this->hasOne(VendorProductVideo::class, 'vendor_product_id', 'id');
   }

   public function skuAttrs()
   {
       return $this->hasMany(VendorProductSkuAttr::class, 'vendor_product_id', 'id');
   }

   public function vendor()
   {
       return $this->hasOne(Vendor::class, 'vendor_id', 'vendor_id');
   }

   public function category()
   {
       return $this->hasOne(Category::class, 'category_id', 'product_category_id');
   }

   public function brand()
   {
       return $this->hasOne(Brand::class, 'brand_id', 'product_brand_id');
   }

   public function auditLog()
   {
        return $this->hasOne(VendorProductAuditLog::class, 'vendor_product_id', 'id');
   }


}