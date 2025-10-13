<?php
//**---------------------------------------------------------------------+
//** 验证器文件 -- 优惠活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\validate\promotion;

use think\Validate;

class TimeDiscountValidate extends Validate
{
    protected $rule = [
        'discount_id'    => 'require|number|>=:1',
        'promotion_name' => 'require|chsDash|max:50',
        'start_time'     => 'require|date',
        'end_time'       => 'require|date',
        'item ' => 'require',
    ];

    protected $scene = [
        'create' => [
            'promotion_name',
            'start_time',
            'end_time',
            'item'
        ],
        'update' => [
            'discount_id',
            'promotion_name',
            'start_time',
            'end_time',
            'item'
        ],
    ];
}
