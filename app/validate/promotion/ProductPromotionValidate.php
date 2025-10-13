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

class ProductPromotionValidate extends Validate
{
    protected $rule = [
        'promotion_name' => 'require|max:100',
        'promotion_type_data' => 'require'
    ];

    protected $message = [
        'promotion_name.require' => '优惠活动名称不能为空',
        'promotion_name.max' => '优惠活动名称最多100个字符',
        'promotion_type_data.require' => '优惠规则不能为空'
    ];

    protected $scene = [
        'create' => [
            'promotion_name',
            'promotion_type_data'
        ],
        'update' => [
            'promotion_name',
            'promotion_type_data'
        ],
    ];
}
