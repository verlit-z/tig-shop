<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 运费模板
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\setting;

use think\Model;

class ShippingTplInfo extends Model
{
    protected $pk = 'id';
    protected $table = 'shipping_tpl_info';

    protected $json = ['region_data'];
    protected $jsonAssoc = true;

    public function shippingType()
    {
        return $this->hasOne(ShippingType::class, 'shipping_type_id', 'shipping_type_id')->bind(["shipping_type_name"]);
    }

    public function getRegionDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setRegionDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}
