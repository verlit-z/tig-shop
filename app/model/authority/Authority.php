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

namespace app\model\authority;

use think\Model;

class Authority extends Model
{
    protected $pk = 'authority_id';
    protected $table = 'authority';
    protected $json = ['child_auth'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;


    public function getChildAuthAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setChildAuthAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}
