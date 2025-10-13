<?php

namespace app\model\salesman;


use app\model\BaseModel;
use utils\Util;

class SalesmanMaterialCategory extends BaseModel
{
    protected $pk = 'category_id';
    protected $table = 'salesman_material_category';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';


    public function getCategoryNameAttr($value, $data)
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