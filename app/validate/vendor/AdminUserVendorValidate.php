<?php

namespace app\validate\vendor;

use think\Validate;

class AdminUserVendorValidate extends Validate
{
    protected $rule = [
        'mobile' => 'max:32',
        'email' => 'email',
        'is_using' => 'in:1,0',
        'username' => 'max:32',
      //  'password' => 'confirm:pwd_confirm',
        'pwd_confirm' => 'confirm:password',
        'role_id' => 'number',
    ];

    protected $message = [
    //    'username.require' => '用户名不能为空',
        'username.max' => '用户名最多32个字符',
        'mobile.max' => '手机号最多32个字符',
        'email.email' => 'email格式不对',
        'is_using.in' => '是否使用参数错误',
        'role_id.number' => '权限id参数错误',
        'pwd_confirm.confirm' => '两次输入的密码不一致',
    ];
}