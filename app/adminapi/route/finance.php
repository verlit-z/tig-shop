<?php

use think\facade\Route;

// 财务组
Route::group('finance', function () {
    // 账户资金面板
    Route::group('accountPanel', function () {
        // 面板
        Route::get('list', 'finance.accountPanel/list');
    });
    // 发票申请
    Route::group('orderInvoice', function () {
        // 发票申请列表
        Route::get('list', 'finance.orderInvoice/list');
        // 发票申请详情
        Route::get('detail', 'finance.orderInvoice/detail');
        // 发票申请编辑
        Route::post('update', 'finance.orderInvoice/update')->append([
            "authorityCheckSubPermissionName" => 'orderInvoiceUpdateManage'
        ]);
        // 发票申请删除
        Route::post('del', 'finance.orderInvoice/del')->append([
            "authorityCheckSubPermissionName" => 'orderInvoiceDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.orderInvoice/batch')->append([
            "authorityCheckSubPermissionName" => 'orderInvoiceBatchManage'
        ]);
    });
    // 交易日志
    Route::group('payLog', function () {
        // 交易日志列表
        Route::get('list', 'finance.payLog/list');
        // 交易日志删除
        Route::post('del', 'finance.payLog/del')->append([
            "authorityCheckSubPermissionName" => 'payLogDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.payLog/batch')->append([
            "authorityCheckSubPermissionName" => 'payLogBatchManage'
        ]);
    });
    // 退款申请
    Route::group('refundApply', function () {
        // 退款申请列表
        Route::get('list', 'finance.refundApply/list');
        // 退款申请详情
        Route::get('detail', 'finance.refundApply/detail');
        // 配置型
        Route::get('config', 'finance.refundApply/config');
        // 退款申请编辑
        Route::post('audit', 'finance.refundApply/audit')->append([
            "authorityCheckSubPermissionName" => 'refundApplyUpdateManage'
        ]);
        // 确认线下转账
        Route::post('offlineAudit', 'finance.refundApply/offlineAudit')->append([
			"authorityCheckSubPermissionName" => 'refundApplyUpdateManage'
		]);
    });
    // 退款记录
    Route::group('refundLog', function () {
        // 退款记录
        Route::get('list', 'finance.refundLog/list');
    });
    // 余额日志
    Route::group('userBalanceLog', function () {
        // 余额日志列表
        Route::get('list', 'finance.userBalanceLog/list');
        // 删除
        Route::post('del', 'finance.userBalanceLog/del')->append([
            "authorityCheckSubPermissionName" => 'userBalanceLogDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.userBalanceLog/batch')->append([
            "authorityCheckSubPermissionName" => 'userBalanceLogBatchManage'
        ]);
    });
    // 增票资质申请
    Route::group('userInvoice', function () {
        // 增票资质申请列表
        Route::get('list', 'finance.userInvoice/list');
        // 配置型
        Route::get('config', 'finance.userInvoice/config');
        // 增票资质申请详情
        Route::get('detail', 'finance.userInvoice/detail');
        // 增票资质申请编辑
        Route::post('update', 'finance.userInvoice/update')->append([
            "authorityCheckSubPermissionName" => 'userInvoiceUpdateManage'
        ]);
        // 删除
        Route::post('del', 'finance.userInvoice/del')->append([
            "authorityCheckSubPermissionName" => 'userInvoiceDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.userInvoice/batch')->append([
            "authorityCheckSubPermissionName" => 'userInvoiceBatchManage'
        ]);
    });
    // 充值申请管理
    Route::group('userRechargeOrder', function () {
        // 充值申请列表
        Route::get('list', 'finance.userRechargeOrder/list');
        // 充值申请详情
        Route::get('detail', 'finance.userRechargeOrder/detail');
        // 充值申请添加
        Route::post('create', 'finance.userRechargeOrder/create')->append([
            "authorityCheckSubPermissionName" => 'userRechargeOrderUpdateManage'
        ]);
        // 充值申请编辑
        Route::post('update', 'finance.userRechargeOrder/update')->append([
            "authorityCheckSubPermissionName" => 'userRechargeOrderUpdateManage'
        ]);
        // 删除
        Route::post('del', 'finance.userRechargeOrder/del')->append([
            "authorityCheckSubPermissionName" => 'userRechargeOrderDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.userRechargeOrder/batch')->append([
            "authorityCheckSubPermissionName" => 'userRechargeOrderBatchManage'
        ]);
    });
    // 提现申请
    Route::group('userWithdrawApply', function () {
        // 提现申请列表
        Route::get('list', 'finance.userWithdrawApply/list');
        // 提现申请详情
        Route::get('detail', 'finance.userWithdrawApply/detail');
        // 提现申请添加
        Route::post('create', 'finance.userWithdrawApply/create')->append([
            "authorityCheckSubPermissionName" => 'userWithdrawApplyUpdateManage'
        ]);
        // 提现申请编辑
        Route::post('update', 'finance.userWithdrawApply/update')->append([
            "authorityCheckSubPermissionName" => 'userWithdrawApplyUpdateManage'
        ]);
        // 删除
        Route::post('del', 'finance.userWithdrawApply/del')->append([
            "authorityCheckSubPermissionName" => 'userWithdrawApplyDelManage'
        ]);
        // 批量操作
        Route::post('batch', 'finance.userWithdrawApply/batch')->append([
            "authorityCheckSubPermissionName" => 'userWithdrawApplyBatchManage'
        ]);
    });

    // 对账单
    Route::group('statement', function () {
        // 对账单列表
        Route::get('getStatementList', 'getStatementList');

        // 对账单统计
        Route::get('getStatementStatisticsList', 'getStatementStatisticsList');

        // 保存对账单下载信息
        Route::post('saveStatementDownload', 'saveStatementDownload');

        // 导出对账单
        Route::get('exportStatement', 'exportStatement');
        // 导出对账单
        Route::get('exportStatementStatistics', 'exportStatementStatistics');
        // 查询字段
        Route::get('getStatementQueryConfig', 'getStatementQueryConfig');

    })->prefix("finance.statement/");


})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);