<?php
use think\facade\Route;

// 首页
Route::group('home', function () {
    // 首页
    Route::group('home', function () {
        // 首页
        Route::get('index', 'index');
        // PC首页
        Route::get('pcIndex', 'pcIndex');
        // 首页今日推荐
        Route::get('getRecommend', 'getRecommend');
        // 首页秒杀
        Route::get('getSeckill', 'getSeckill');
        // 首页优惠券
        Route::get('getCoupon', 'getCoupon');
        // 首页分类栏
        Route::get('mobileCatNav', 'mobileCatNav');
        // 移动端导航栏
        Route::get('mobileNav', 'mobileNav');
        // 个人中心状态
        Route::get('memberDecorate', 'memberDecorate');
        // 客服
        Route::get('getCustomerServiceConfig', 'customerServiceConfig');
        // 友情链接
        Route::get('friendLinks', 'friendLinks');
    })->prefix("home.home/");
    //装修模板导入
    Route::group('share', function () {
        //装修导入查询
        Route::get('import', 'home.share/import');
    });
});