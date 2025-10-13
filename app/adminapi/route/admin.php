<?php

use think\facade\Route;

//主账号模块
Route::group('admin', function () {

    Route::group('adminAccount', function () {
        //根据店铺或供应商ID查询主账号信息
        Route::get('getMainAccount', 'getMainAccount');
        //根据主账号ID和类型查询账号列表
        Route::get('pageShopOrVendor', 'pageShopOrVendor');

        // 根据主账号ID和店铺ID/供应商ID绑定账号
        Route::post('bindMainAccount', 'bindMainAccount');
        // 修改主账号信息
        Route::post('updateMainAccount', 'updateMainAccount');
        // 修改主账号密码
        Route::post('updateMainAccountPwd', 'updateMainAccountPwd');
        // 账号管理列表
        Route::get('pageAdminUser', 'pageAdminUser');

    })->prefix("admin.adminAccount/");
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);