<?php

use think\facade\Route;

// 访问日志控制器
Route::group('sys', function () {
    // 日志
    Route::group('accessLog', function () {
        // 列表
        Route::get('list', 'sys.accessLog/list');
        // 详情
        Route::get('detail', 'sys.accessLog/detail');
        // 编辑
        Route::post('create', 'sys.accessLog/create')->append([
			"authorityCheckSubPermissionName" => 'accessLogModifyManage'
		]);
        // 编辑
        Route::post('update', 'sys.accessLog/update')->append([
			"authorityCheckSubPermissionName" => 'accessLogModifyManage'
		]);
        // 删除
        Route::post('del', 'sys.accessLog/del')->append([
			"authorityCheckSubPermissionName" => 'accessLogModifyManage'
		]);
        // 更新字段
        Route::post('updateField', 'sys.accessLog/updateField')->append([
			"authorityCheckSubPermissionName" => 'accessLogModifyManage'
		]);
        // batch批量操作
        Route::post('batch', 'sys.accessLog/batch')->append([
			"authorityCheckSubPermissionName" => 'accessLogModifyManage'
		]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'accessLogManage'
    ]);
});