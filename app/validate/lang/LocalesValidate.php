<?php
namespace app\validate\lang;


use think\Validate;

class LocalesValidate extends Validate
{
    protected $rule = [
        'locale_code' => 'require|max:100',
        "language" => 'require|max:100',
        'sort' => 'require',
    ];

    protected $message = [
        'locale_code.require' => '语言地区代码不能为空',
        'locale_code.max' => '语言地区代码最多100个字符',
        'language.require' => '语言名称不能为空',
        'language.max' => '语言名称最多100个字符',
        'sort.require' => '排序不能为空'
    ];

    protected $scene = [
        'create' => [
            'locale_code',
            'language',
            'sort'
        ],
        'update' => [
            'locale_code',
            'language',
            'sort'
        ],
    ];
}
