<?php

use think\facade\Route;

// 公共方法组
Route::group('common', function () {
    Route::group('cacheManage', function () {
        // 清除缓存
        Route::post('cleanup', 'common.cacheManage/cleanup');
    });

    Route::group('tipsManage', function () {
        // 列表
        Route::get('list', 'common.tipsManage/list');
    });

    Route::group('verification', function () {
        Route::get('captcha', 'common.verification/captcha');
        // 一次验证
        Route::post('check', 'common.verification/check');
    });

    Route::group('csrf', function () {
        // 上传文件
        Route::get('create', 'create');
    })->prefix('common.csrf/');
});