<?php

namespace app\validate\vendor;

use think\Validate;

class VendorProductValidate extends Validate
{
    protected $rule = [
        'product_name' => 'require|max:100',
        'product_sn' => 'checkUnique',
        'product_category_id' => 'require',
        'product_sn_generate_type' => 'require',
        'product_state' => 'require',
        'sku_type' => 'require',
    ];

    protected $message = [
        'product_name.require' => '商品管理名称不能为空',
        'product_name.max' => '商品管理名称最多100个字符',
        'product_category_id.require' => '商品类目ID不能为空',
        'product_sn_generate_type.require' => '商品编码生成类型能为空',
        'product_state.require' => '商品状态不能为空',
        'sku_type.require' => '规格类型不能为空',
    ];
}