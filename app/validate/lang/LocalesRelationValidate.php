<?php

namespace app\validate\lang;


use think\Validate;

class LocalesRelationValidate extends Validate
{
    protected $rule = [
        'code' => 'require|max:100',
        "name" => 'require|max:100',
    ];

    protected $message = [
        'code.require' => '浏览器语言地区代码不能为空',
        'code.max' => '语言地区代码最多100个字符',
        'name.require' => '名称不能为空',
    ];

    protected $scene = [
        'create' => [
            'locales_id',
            'name',
            'code'
        ],
        'update' => [
            'locales_id',
            'name',
            'code'
        ],
    ];
}
