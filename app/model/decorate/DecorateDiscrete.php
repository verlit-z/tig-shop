<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 散模块装修
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\decorate;

use think\Model;

class DecorateDiscrete extends Model
{
    protected $pk = 'id';
    protected $table = 'decorate_discrete';
    protected $json = ['data'];
    protected $jsonAssoc = true;

    public function getDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}
