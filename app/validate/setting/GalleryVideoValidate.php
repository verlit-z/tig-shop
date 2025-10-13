<?php

namespace app\validate\setting;

use think\Validate;

class GalleryVideoValidate extends Validate
{
    protected $rule = [
        'name' => 'require|max:100',
    ];

    protected $message = [
        'name.require' => '视频库名称不能为空',
        'name.max' => '相册名称最多100个字符',
    ];

    protected $scene = [
        'create' => [
            'name',
        ],
        'update' => [
            'name',
        ],
    ];
}