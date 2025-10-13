<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 订单拆分日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use think\Model;

class OrderSplitLog extends Model
{
    protected $pk = 'log_id';
    protected $table = 'order_split_log';
    protected $json = ['parent_order_data'];

    public function getParentOrderDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setParentOrderDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

}
