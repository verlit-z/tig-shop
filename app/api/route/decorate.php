<?php

use think\facade\Route;

// 首页
Route::group('decorate', function () {

    Route::group('discrete', function () {
        // 首页开屏广告 api/decorate/discrete/getOpenAdvertising
        Route::get('getOpenAdvertising', 'decorate.discrete/getOpenAdvertising');

    });
});