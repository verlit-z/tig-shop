<?php
namespace app\validate\salesman;

use think\Validate;

class SalesmanContentValidate extends Validate
{
    protected $rule = [
        'title' => 'require|max:80',
        'img' => 'require',
        'start_time' => 'require|date'
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'title.max' => '标题最多80个字符',
        'img.require' => '图片不能为空',
        'start_time.require' => '推送时间不能为空',
        'start_time.date' => '推送时间格式错误'
    ];

    protected $scene = [
        'create' => [
            'title',
            'img',
            'start_time'
        ],
        'update' => [
            'title',
            'img',
            'start_time'
        ]
    ];
}