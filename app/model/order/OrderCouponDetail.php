<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 订单日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use app\model\authority\AdminUser;
use app\model\user\User;
use think\Model;
use utils\Time;

class OrderCouponDetail extends Model
{
    protected $pk = 'order_coupon_detail_id';
    protected $table = 'order_coupon_detail';

    protected $createTime = false;
    protected $updateTime = false;
}
