<?php
use think\facade\Route;
Route::group('appVersion', function () {
    //获取app版本
    Route::post('getAppUpdate', 'common.appVersion/getAppUpdate');
});
