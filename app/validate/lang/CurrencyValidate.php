<?php
namespace app\validate\lang;

use think\Validate;

class CurrencyValidate extends Validate
{
    protected $rule = [
        'name' => 'require|max:10',
        'symbol' => 'require|max:10',
    ];

    protected $message = [
        'name.require' => '货币名称不能为空',
        'name.max' => '货币名称最多10个字符',
        'symbol.require' => '货币符号不能为空',
        'symbol.max' => '货币符号最多10个字符',
    ];

    protected $scene = [
        'create' => [
            'name',
            'symbol',
            'locales_id'
        ],
        'update' => [
            'name',
            'symbol',
            'locales_id'
        ],
    ];
}
