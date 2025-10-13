<?php

use think\facade\Route;

// 公共方法
Route::group('common', function () {
    // 配置
    Route::group('config', function () {
        // 基本配置
        Route::get('base', 'base');
        Route::get('initConfigSettings', 'base');
        Route::get('themeSettings', 'themeSettings');
        // 基本配置
        Route::get('mobileAreaCode', 'mobileAreaCode');
        // 售后服务配置
        Route::get('afterSalesService', 'afterSalesService');
    })->prefix('common.config/');

//记录日志
    Route::get('log', 'common.log/index');
    // PC
    Route::group('pc', function () {
        // 获取头部导航
        Route::get('getHeader', 'getHeader');
        // 获取PC导航栏
        Route::get('getNav', 'getNav');
        // 获取PC分类抽屉
        Route::get('getCatFloor', 'getCatFloor');
    })->prefix('common.pc/');
    // PC
    Route::group('util', function () {
        // 获取二维码
        Route::get('qrCode', 'qrCode');
        //获取小程序二维码
        Route::get('miniCode', 'getMiniProgramCode');
    })->prefix('common.util/');
    // 推荐位
    Route::group('recommend', function () {
        // 猜你喜欢
        Route::get('guessLike', 'common.recommend/guessLike');
        // 猜你喜欢
        Route::get('getProductIds', 'common.recommend/getProductIds');
    });
    // 验证
    Route::group('verification', function () {
        // 获取验证码
        Route::post('captcha', 'captcha');
        // 一次验证
        Route::post('check', 'check');
        // 二次验证
        Route::post('verification', 'verification');
    })->prefix('common.verification/');

    // 货币
    Route::group('currency', function () {
        // 获得货币列表
        Route::get('getCurrency', 'getCurrency');
    })->prefix('common.currency/');

    Route::group('csrf', function () {
        // 上传文件
        Route::get('create', 'create');
    })->prefix('common.csrf/');
});