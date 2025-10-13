<?php

// +----------------------------------------------------------------------
// | 商城设置项，后台新增字段时需在此处同步添加字段和默认值（此处修改值无效，请通过后台修改）
// +----------------------------------------------------------------------
return [
    // 基础设置项
    'base' => [
        'shop_name' => '',
        'shop_title' => '',
        'shop_title_suffix' => '',
        'shop_keywords' => '',
        'shop_desc' => '',
        'shop_logo' => '',
        'default_avatar' => '',
        'ico_img' => '',
        'auto_redirect' => '',
        'pc_domain' => '',
        'h5_domain' => '',
        'admin_domain' => '',
        'is_open_redis' => 0,
        'is_open_mobile_area_code' => 0,
        'session_open_redis' => 0,
        'redis_host' => '127.0.0.1',
        'redis_host_port' => 6379,
        'redis_host_password' => '',
        'is_open_queue' => 0,
        'comment_check' => 0,
        'message_check' => 0,
        'username_prefix' => '',
        'company_address' => '',
        'shop_icp_no' => '',
        'shop_icp_no_url' => '',
        'shop_110_no' => '',
        'shop_110_link' => '',
        'shop_reg_closed' => 0,
        'close_order' => 0,
        'upload_save_full_domain' => 0,
    ],
    'base_licensed_data' => [
        'admin_light_logo' => '',
        'admin_dark_logo' => '',
        'powered_by' => '',
        'powered_by_status' => 0,
        'shop_company' => '',
    ],
    // 支付相关
    'payment' => [
        'use_surplus' => 1,
        'use_cod' => 1,
        'use_points' => 1,
        'use_coupon' => 1,
        // 微信支付
        'use_wechat' => 1,
        'wechat_mchid_type' => 1,
        'wechat_pay_mchid' => '',
        'wechat_pay_sub_mchid' => '',
        'wechat_pay_key' => '',
        'wechat_pay_serial_no' => '',
        'wechat_pay_private_key' => 0,
        'wechat_pay_certificate' => 0,
        'wechat_pay_platform_certificate' => 0,
        'wechat_pay_check_type' => 1,
        'wechat_pay_public_key' => '',
        'wechat_pay_public_key_id' => '',
        // 支付宝支付
        'use_alipay' => 1,
        'alipay_appid' => '',
        'alipay_mobile_appid' => '',
        'alipay_rsa_private_key' => '',
        'alipay_rsa_public_key' => '',
        'alipay_rsa_sign_type' => 0,
        'alipay_rsa_sign_type_value' => 'RSA2',
        'alipay_rsa_sign_type_value_list' => 'RSA2',
        // 线下支付
        'use_offline' => 1,
        'offline_pay_bank' => '',
        'offline_pay_company' => '',
        //yabanpay支付
        'use_yabanpay' => 0,
        'use_yabanpay_wechat' => 0,
        'use_yabanpay_alipay' => 0,
        'use_yunpay' => 0,
        'yunpay_uid' => '',
        'yunpay_secret_key' => '',
        'yabandpay_uid' => '',
        'yabandpay_secret_key' => '',
        'yabanpay_currency' => '',
        'yabanpay_currency_list' => [
            ['name' => '欧元', 'value' => 'EUR'],
            ['name' => '人民币', 'value' => 'CNY'],
            ['name' => '港元', 'value' => 'HKD'],
            ['name' => '瑞士法郎', 'value' => 'CHF'],
            ['name' => '丹麦克朗', 'value' => 'DKK'],
            ['name' => '瑞典克朗', 'value' => 'SEK'],
            ['name' => '波兰兹罗提', 'value' => 'PLN'],
            ['name' => '挪威克朗', 'value' => 'NOK'],
            ['name' => '美元', 'value' => 'USD'],
            ['name' => '匈牙利福林', 'value' => 'HUF'],
            ['name' => '捷克克朗', 'value' => 'CZK'],
        ],
        // payPal支付
        'use_paypal' => 0,
        'paypal_client_id' => '',
        'paypal_secret' => '',
        'paypal_currency' => '',
        'paypal_currency_list' => [
            ['name' => '澳元', 'value' => 'AUD'],
            ['name' => '加元', 'value' => 'CAD'],
            ['name' => '欧元', 'value' => 'EUR'],
            ['name' => '英镑', 'value' => 'GBP'],
            ['name' => '日元', 'value' => 'JPY'],
            ['name' => '港元', 'value' => 'HKD'],
            ['name' => '美元', 'value' => 'USD'],
        ],
    ],
    //商户的设置
    'merchant' => [
        'person_apply_enabled' => 1,
        'merchant_apply_need_check' => 1,
        'max_shop_count' => 5,
        'shop_agreement' => '',
        'shop_rank_date_rage' => '',
        'enabled_commission_order' => '',
        'default_admin_prefix' => '',
    ],
    // 店铺设置
    'shop' => [
        'shop_product_need_check' => 0,
        'max_recommend_product_count' => 5,
        'max_sub_administrator' => 5
    ],

    // 商品设置
    'base_product' => [
        // 商品基础设置
        'basic_product' => [
            'dollar_sign' => '¥',
            'dollar_sign_cn' => '元',
            'sn_prefix' => 'SN',
            'price_format' => 1,
            'default_storage' => 1,
            'goods_url_type' => 0
        ],
        // 显示相关
        'show_related' => [
            'is_show_price_trend' => 1,
            'show_selled_count' => 1,
            'show_marketprice' => 1,
            'is_spe_goods_number' => 1,
            'spe_goods_number_1' => 10,
            'spe_goods_number_2' => 30,
            'spe_goods_number_3' => 50,
            'page_size' => 20,
            'history_number' => 20,
            'ly_brand_type' => '',
            'comment_default_tag' => '',
        ],
        'market_price_rate' => 1.2
    ],
    // 购物设置
    'base_shopping' => [
        'shopping_global' => [
            'auto_split_paid_order' => 0,
            'child_area_need_region' => 0,
        ],
        'auto_cancel_order_minute' => 15,
        'settlement_setting' => [
            'use_bonus' => 1,
            'use_surplus' => 1,
        ],
        'points_setting' => [
            'use_integral' => 1,
            'integral_name' => '积分',
            'integral_scale' => 1,
            'integral_percent' => 50,
            'order_send_point' => 1,
            'comment_send_point' => 5,
            'show_send_point' => 5,
            'use_qiandao_point' => 1,
        ],
        'invoice_setting' => [
            'can_invoice' => 1,
            'invoice_content' => '',
            'invoice_added' => 1,
        ],
        'activity_setting' => [
            'lottery_closed' => 0,
            'lottery_point' => 5,
            'is_open_pin' => 1,
            'is_open_bargain' => 1,
        ],
        'after_sales_setting' => [
            'return_consignee' => '',
            'return_mobile' => '',
            'return_address' => '',
        ]
    ],
    // 短信设置
    'base_sms' => [
        'sms_key_id' => '',
        'sms_key_secret' => '',
        'sms_sign_name' => '',
        'sms_shop_mobile' => '',
    ],
    // 邮箱通知
    'base_mailbox' => [
        'service_email' => '',
        'send_confirm_email' => 0,
        'order_pay_email' => 1,
        'send_service_email' => 1,
        'send_ship_email' => 0,
    ],
    // 显示设置
    'base_display' => [
        "general_setting" => [
            'search_keywords' => '',
            'msg_hack_word' => 'http,link,请填入非法关键词',
            'is_open_pscws' => '',
            'self_store_name' => '官方自营',
        ],
        'region_setting' => [
            'shop_default_regions' => [],
            'default_country' => 1,
        ],
        'show_cat_level' => 0,
    ],
    // 客服设置
    "base_kefu" => [
        'kefu_setting' => [
            'kefu_type' => 1,
            'kefu_yzf_type' => 1,
            'kefu_yzf_sign' => '',
            'kefu_workwx_id' => '',
            'kefu_code' => '',
            'kefu_code_blank' => 3,
            'kefu_javascript' => '',
            'wap_kefu_javascript' => '',
            'kefu_name' => '',
            'corp_id' => ''
        ],
        'kefu_info' => [
            'kefu_phone' => '',
            'kefu_time' => '',
        ]
    ],
    // 微信公众号配置
    "base_api_wechat" => [
        'wechat_oauth' => 0,
        'wechat_appId' => '',
        'wechat_appSecret' => '',
        'wechat_server_url' => '/api/user/login/wechat_server',
        'wechat_server_token' => '',
        'wechat_server_secret' => ''
    ],
    // 微信商户配置
    "base_api_wechat_merchant" => [
        'wechat_pay_mchid_type' => '',
        'wechat_pay_mchid' => '',
        'wechat_pay_sub_mchid' => '',
        'wechat_pay_key' => '',
    ],
    // 微信小程序配置
    "base_api_mini_program" => [
        'wechat_miniProgram_appId' => '',
        'wechat_miniProgram_secret' => '',
    ],
    // 微信APP支付配置
    "base_api_app_pay" => [
        'wechat_pay_app_id' => '',
        'wechat_pay_app_secret' => '',
    ],
    // ICO 图标
    "base_api_icon" => [
        'ico_tig_css' => '',
        'ico_defined_css' => '',
    ],
    // 存储配置
    "base_api_storage" => [
        'storage_type' => 1,
        'storage_local_url' => '',
        'storage_oss_url' => '',
        'storage_oss_access_key_id' => '',
        'storage_oss_access_key_secret' => '',
        'storage_oss_bucket' => '',
        'storage_oss_region' => '',
        'storage_cos_url' => '',
        'storage_cos_secret_id' => '',
        'storage_cos_secret_key' => '',
        'storage_cos_bucket' => '',
        'storage_cos_region' => '',
        'storage_save_full_path' => 0,
    ],
    // 商品采集配置
    "base_api_collection" => [
        'onebound_key' => '',
        'onebound_secret' => '',
    ],
    //多语言配置
    "base_api_lang" => [
        'lang_on' => 0,
        'lang_type' => 1,
        'lang_volcengine_access_key' => '',
        'lang_volcengine_secret' => '',
    ],
    'logistics' => [
        'kdniao' => [
            'api_key' => '',
            'business_id' => ''
        ],
        'kd100' => [],
        'province_name' => '',
        'city_name' => '',
        'area_name' => '',
        'address' => ''
    ]
];
