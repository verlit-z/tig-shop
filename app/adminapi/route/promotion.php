<?php
use think\facade\Route;

// 营销组
Route::group('promotion', function () {
    // 活动管理
    Route::group('promotion', function () {
        // 活动管理列表
        Route::get('list', 'list');
        //获取活动数量
        Route::get('getPromotionCount', 'getPromotionCount');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'promotionManage'
    ])->prefix('promotion.promotion/');
    // 优惠券管理
    Route::group('coupon', function () {
        // 优惠券列表
        Route::get('list', 'list');
        // 优惠券配置
        Route::get('config', 'config');
        // 优惠券详情
        Route::get('detail', 'detail');
        // 优惠券添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'promotionCouponModifyManage'
        ]);
        // 优惠券编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'promotionCouponModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'promotionCouponModifyManage'
        ]);
        // 优惠券删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'promotionCouponModifyManage'
        ]);
        // 优惠券批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'promotionCouponModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'couponManage'
    ])->prefix('promotion.coupon/');

    // 积分商品管理
    Route::group('pointsExchange', function () {
        // 积分商品列表
        Route::get('list', 'list');
        // 积分商品详情
        Route::get('detail', 'detail');
        // 积分商品添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'promotionPointsExchangeModifyManage'
        ]);
        // 积分商品编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'promotionPointsExchangeModifyManage'
        ]);
        // 更新单个字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'promotionPointsExchangeModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'promotionPointsExchangeModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'promotionPointsExchangeModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'pointsExchangeManage'
    ])->prefix('promotion.pointsExchange/');

    // 优惠活动管理
    Route::group('productPromotion', function () {
        // 优惠活动列表
        Route::get('list', 'list');
        // 优惠活动配置
        Route::get('config', 'config');
        // 优惠活动详情
        Route::get('detail', 'detail');
        // 优惠活动添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productPromotionModifyManage'
        ]);
        // 优惠活动编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productPromotionModifyManage'
        ]);
        // 更新单个字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'productPromotionModifyManage'
        ]);
        // 优惠活动删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productPromotionModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productPromotionModifyManage'
        ]);
        // 活动冲突列表
        Route::get('conflict', 'conflictList');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productPromotionManage'
    ])->prefix('promotion.productPromotion/');

    // 活动赠品
    Route::group('productGift', function () {
        // 活动赠品列表
        Route::get('list', 'list');
        // 活动赠品详情
        Route::get('detail', 'detail');
        // 活动赠品添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productGiftModifyManage'
        ]);
        // 活动赠品编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productGiftModifyManage'
        ]);
        // 活动赠品删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productGiftModifyManage'
        ]);

    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productGiftManage'
    ])->prefix('promotion.productGift/');


    // 限时折扣
    Route::group('timeDiscount', function () {
        // 限时折扣列表
        Route::get('list', 'list');
        // 限时折扣详情
        Route::get('detail', 'detail');
        // 限时折扣添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'timeDiscountModifyManage'
        ]);
        // 限时折扣编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'timeDiscountModifyManage'
        ]);
        // 限时折扣删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'timeDiscountModifyManage'
        ]);
		// 批量操作
		Route::post('batch', 'batch')->append([
			"authorityCheckSubPermissionName" => 'timeDiscountModifyManage'
		]);

    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'timeDiscountManage'
    ])->prefix('promotion.timeDiscount/');

    // 余额充值
    Route::group('rechargeSetting', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'rechargeSettingModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'rechargeSettingModifyManage'
        ]);
        // 更新单个字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'rechargeSettingModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'rechargeSettingModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'rechargeSettingModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'rechargeSettingManage'
    ])->prefix('promotion.rechargeSetting/');

    // 秒杀活动
    Route::group('seckill', function () {
        // 列表
        Route::get('list', 'list');
        // 装修秒杀列表
        Route::get('listForDecorate', 'listForDecorate');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'seckillModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'seckillModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'seckillModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'seckillModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'seckillManage'
    ])->prefix('promotion.seckill/');


    // 拼团活动
    Route::group('productTeam', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productTeamModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productTeamModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productTeamModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productTeamModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productTeamManage'
    ])->prefix('promotion.productTeam/');

    // 积分签到
    Route::group('signInSetting', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'signInSettingModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'signInSettingModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'signInSettingModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'signInSettingModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'signInSettingManage'
    ])->prefix('promotion.signInSetting/');

	// 小程序直播
	Route::group('wechatLive', function () {
		// 列表
		Route::get('list', 'list');
		// 详情
		Route::get('detail', 'detail');
		// 编辑
		Route::post('update', 'update')->append([
			"authorityCheckSubPermissionName" => 'wechatLiveModifyManage'
		]);
		// api更新直播间
		Route::get('refresh', 'refreshByApi');
	})->append([
		//用于权限校验的名称
		'authorityCheckAppendGroupName' => 'wechatLiveManage'
	])->prefix('promotion.wechatLive/');
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);
