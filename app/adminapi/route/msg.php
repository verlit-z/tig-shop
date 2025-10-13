<?php
use think\facade\Route;

// 消息管理组
Route::group('msg', function () {
    // 管理员消息
    Route::group('adminMsg', function () {
        // 列表
        Route::get('list', 'list');
        //获取消息类别
        Route::get('msgTypeArr', 'msgTypeArr');
        // 设置单个已读
        Route::post('setReaded', 'setReaded')->append([
			"authorityCheckSubPermissionName" => 'adminMsgModifyManage'
		]);
        // 设置全部已读
        Route::post('setAllReaded', 'setAllReaded')->append([
			"authorityCheckSubPermissionName" => 'adminMsgModifyManage'
		]);

        // 统计
        Route::get('msgCount', 'getMsgCount');
		// 配置项
		Route::get('config', 'config');
        //获取消息类别
        Route::get('msgTypeArr', 'msgTypeArr');
    })->prefix("msg.adminMsg/");
});