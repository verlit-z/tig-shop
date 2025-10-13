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

class ProductGiftValidate extends Validate
{
    protected $rule = [
        'gift_id' => 'require|number|>=:1',
        'gift_name' => 'require|chsDash|max:50',
        'product_id' => 'require|number|>=:1',
        'gift_stock' => "require|number|>=:1",
    ];

    protected $message = [
        'gift_id.require' => '请选择赠品',
        'gift_id.number' => '赠品ID必须为数字',
        'gift_name.require' => '赠品名称不能为空',
        'gift_name.chsDash' => '赠品名称只能是汉字、字母、数字和下划线_及破折号-',
        'gift_name.max' =>'赠品名称最多50个字符',
        'product_id.require' => '请选择赠品商品',
        'product_id.number' => '赠品商品ID必须为数字',
        'gift_stock.require' => '请输入赠品库存',
        'gift_stock.number' => '赠品库存必须为数字',
    ];

    protected $scene = [
        'create' => [
            'gift_name','product_id','gift_stock'
        ],
        'update' => [
            'gift_id','gift_name','product_id','gift_stock'
        ],
    ];
}
