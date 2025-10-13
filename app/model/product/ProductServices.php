<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 商品服务
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\product;

use think\Model;
use utils\Util;

class ProductServices extends Model
{
    protected $pk = 'product_service_id';
    protected $table = 'product_services';

    public function getProductServiceNameAttr($value, $data)
    {
        if (empty($value)) {
            return $value;
        }
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
