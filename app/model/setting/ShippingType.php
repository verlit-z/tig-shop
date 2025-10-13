<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 配送类型
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\setting;

use think\Model;
use utils\Util;

class ShippingType extends Model
{
    protected $pk = 'shipping_type_id';
    protected $table = 'shipping_type';
    public function logisticsCompany()
    {
        return $this->hasOne(LogisticsCompany::class, 'logistics_id', 'shipping_default_id')->bind(["logistics_name"]);
    }

    public function getShippingTypeNameAttr($value, $data)
    {
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }


    public function getShippingTimeDescAttr($value, $data)
    {
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

}
