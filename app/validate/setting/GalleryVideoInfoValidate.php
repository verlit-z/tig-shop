<?php

namespace app\validate\setting;

use think\Validate;

class GalleryVideoInfoValidate extends Validate
{
    protected $rule = [
        'video_name' => 'require|max:100',
    ];

    protected $message = [
        'video_name.require' => '相册图片名称不能为空',
        'video_name.max' => '相册图片名称最多100个字符',
    ];

    protected $scene = [];
}