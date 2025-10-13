<?php
use think\facade\Route;

// 系统配置
Route::group('sys', function () {
    // 地区
    Route::group('region', function () {
        // 获取系统配置
        Route::get('getRegion', 'sys.region/getRegion');
        // 获得所有省份接口
        Route::get('getProvinceList', 'sys.region/getProvinceList');
        // 获得用户所在省份
        Route::get('getUserRegion', 'sys.region/getUserRegion');
    });
});