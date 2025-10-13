<?php
//**---------------------------------------------------------------------+
//**   分类模型
//**---------------------------------------------------------------------+
//**   版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//**   作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//**   提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\product;

use think\Model;
use utils\Util;

class Category extends Model
{
    protected $pk = 'category_id';
    protected $table = 'category';

//    public function getCategoryNameAttr($value, $data)
//    {
//
//        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
//            $cache = Util::lang($value, '', [], 3);
//            if ($cache) {
//                return $cache;
//            } else {
//
//                return $value;
//            }
//        } else {
//            return $value;
//        }
//    }
}
