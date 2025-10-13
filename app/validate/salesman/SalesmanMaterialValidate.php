<?php

namespace app\validate\salesman;

use think\Validate;

class SalesmanMaterialValidate extends Validate
{
    protected $rule = [
        'content' => 'require',
        'pics' => 'require',
        'category_id' => 'require',
        'product_id' => 'require',
    ];

    protected $message = [
        'content.require' => '内容不能为空',
        'pics.require' => '图片不能为空',
        'category_id.require' => '请选择分类',
        'product_id.require' => '请选择商品',
    ];

    protected $scene = [
        'create' => [
            'content',
            'pics',
            'category_id',
            'product_id'
        ],
        'update' => [
            'content',
            'pics',
            'category_id',
            'product_id'
        ]
    ];
}