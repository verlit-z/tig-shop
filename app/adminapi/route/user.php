<?php

use think\facade\Route;

// 会员管理模块
Route::group('user', function () {
    // 会员留言
    Route::group('feedback', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'feedbackModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'feedbackModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'feedbackModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'feedbackModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'feedbackManage'
    ])->prefix("user.feedback/");
    // 会员
    Route::group('user', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 编辑
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'userCreateManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'userModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'userDelManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'userModifyFieldManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'userBatchManage'
        ]);
        // 搜索会员
        Route::get('search', 'searchByMobile');
        // 资金明细
        Route::get('userFundDetail', 'userFundDetail');
        // 资金管理
        Route::post('fundManagement', 'fundManagement')->append([
            "authorityCheckSubPermissionName" => 'fundManagementManage'
        ]);
        //退出登陆
        Route::post('logout', 'logout');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'userManage'
    ])->prefix("user.user/");
    // 站内信
    Route::group('userMessageLog', function () {
        // 列表
        Route::get('list', 'list');
        // 列表
        Route::get('detail', 'detail');
        // 新增
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'userMessageLogModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'userMessageLogModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'userMessageLogModifyManage'
        ]);
        // 撤回
        Route::post('recall', 'recall')->append([
            "authorityCheckSubPermissionName" => 'userMessageLogModifyManage'
        ]);
		// 批量操作
		Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'userMessageLogModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'messageLogManage'
    ])->prefix("user.userMessageLog/");
    // 会员积分日志
    Route::group('userPointsLog', function () {
        // 列表
        Route::get('list', 'list');
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'userPointsLogModifyManage'
        ]);
        //获取会员积分
        Route::get('getPoints', 'getPoints');
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'userPointsLogModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'integralLogManage'
    ])->prefix("user.userPointsLog/");

    // 会员等级
    Route::group('userRank', function () {
        // 列表
        Route::get('list', 'list')->append([
            "authorityCheckSubPermissionName" => 'levelManageManage'
        ]);
        // 详情
        Route::get('detail', 'detail');
        // 编辑
        Route::post('update', 'update');
    })->prefix("user.userRank/");

    // 会员等级变更日志
    Route::group('userRankLog', function () {
        // 列表
        Route::get('list', 'list');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'userRankLogManage'
    ])->prefix("user.userRankLog/");

	// 会员企业认证
	Route::group('userCompany', function () {
		// 列表
		Route::get('list', 'list');
		// 详情
		Route::get('detail', 'detail');
		// 审核
		Route::post('audit', 'audit');
		// 删除
		Route::post('del', 'del');
	})->append([
		//用于权限校验的名称
		'authorityCheckAppendName' => 'userCertificationManage'
	])->prefix("user.userCompany/");
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);