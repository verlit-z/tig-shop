<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 角色管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\authority;

use think\Model;

class AdminRole extends Model
{
    protected $pk = 'role_id';
    protected $table = 'admin_role';
    protected $json = ["authority_list"];
    protected $jsonAssoc = true;


    public function getAuthorityListAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setAuthorityListAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}
