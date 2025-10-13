<?php

namespace app\validate\vendor;

use think\Validate;

class VendorValidate extends Validate
{
    protected $rule = [
        'vendor_name' => 'require|max:250',
        'type' => 'in:1,2',
        'kefu_phone' => 'max:20',
        'status' =>  'in:1,2',
        'contact_mobile' => 'max:250',
        'vendor_logo' => 'max:250',
        'vendor_data' => 'array',
        'person_data' => 'array',
    ];

    protected $message = [
        'vendor_name.require' => '供应商名称不能为空',
        'product_name.max' => '供应商名称最多250个字符',
        'type.in' => '供应商类型参数错误',
        'kefu_phone.max' => '客服电话最多20个字符',
        'status.in' => '状态参数错误',
        'contact_mobile.max' => '供应商电话最多250个字符',
        'vendor_logo.max' => '供应商logo最多250个字符',
        'vendor_data.array' => '供应商信息参数格式错误',
        'person_data.array' => '个人信息参数格式错误',
    ];
}