<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('APP_HOST', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app' => 'api',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map'          => [
        'adminapi' => 'adminapi',
        'api' => 'api',
        'pc' => 'api',
        'h5' => 'api',
        'mobile' => 'api',
        'app' => 'api',
        'im' => 'im'
    ],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,

    'allow_cross_domain' => [
        'http://demo.tigshop.cn',
        'https://demo.tigshop.cn',
        'http://localhost:5173',
        'http://localhost:5174',
        'http://192.168.5.91:5173',
        'http://192.168.5.96:5173',
        'http://192.168.5.111:5173',
        'http://192.168.5.111:3000',
        'http://192.168.5.90:5173',
        'http://192.168.5.90:5174',
        'http://192.168,5.79:5173'
    ],
    'kf'    =>  [
        'yzf_url' => 'https://yzf.qq.com/xv/web/static/chat/index.html?sign=',//腾讯云智服
        'work_url' => 'https://work.weixin.qq.com/kfid/',//企业客服
    ],
    'version_type' => 'B2C',

    'version' => '5.1.0',

    //是否是多商户版本
    'IS_MERCHANT' => env('IS_MERCHANT', 0),

    //是否是PRO版本
    'IS_PRO' => env('IS_PRO', 0),

    //是否B2B版本
    'IS_B2B' => env('IS_B2B', 0),

    //是否供应商版本
    'IS_VENDOR' => env('IS_VENDOR', 0),

    //是否是跨境版本
    'IS_OVERSEAS' => env('IS_OVERSEAS', 0),

    'default_company' => env('DEFAULT_COMPANY', 'Copyright © 2024 Tigshop. All Rights Reserved'),
];
