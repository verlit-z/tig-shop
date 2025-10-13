<?php

use think\facade\Route;

//订单模块
Route::group('order', function () {
    //售后管理
    Route::group('aftersales', function () {
        // 列表
        Route::get('list', 'list');
        //申请类型
        Route::get('applyType', 'applyType');
        //退换货状态
        Route::get('returnGoodsStatus', 'returnGoodsStatus');

        // 详情接口
        Route::get('detail', 'detail');
        // 同意或拒接售后接口
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'aftersalesModifyManage'
        ]);
        // 售后确认收货接口
        Route::post('receive', 'receive')->append([
            "authorityCheckSubPermissionName" => 'aftersalesModifyManage'
        ]);
        // 提交售后反馈记录
        Route::post('record', 'record')->append([
            "authorityCheckSubPermissionName" => 'aftersalesModifyManage'
        ]);
        // 售后完结
        Route::post('complete', 'complete')->append([
            "authorityCheckSubPermissionName" => 'aftersalesModifyManage'
        ]);
    })->prefix("order.aftersales/");
    //订单管理
    Route::group('order', function () {
        //订单列表
        Route::get('list', 'list');
        //订单详情
        Route::get('detail', 'detail');
        //获取电子面单接口
        Route::get('orderWayBill', 'printOrderWaybill');
        //父订单详情
        Route::get('parentDetail', 'parentOrderDetail');
        //订单发货
        Route::post('deliver', 'deliver')->append([
            "authorityCheckSubPermissionName" => 'orderDeliverManage'
        ]);
        //订单收货
        Route::post('confirmReceipt', 'confirmReceipt')->append([
            "authorityCheckSubPermissionName" => 'orderConfirmReceiptManage'
        ]);
        //订单修改收货人信息
        Route::post('modifyConsignee', 'modifyConsignee')->append([
            "authorityCheckSubPermissionName" => 'orderModifyConsigneeManage'
        ]);
        //修改配送信息
        Route::post('modifyShipping', 'modifyShipping')->append([
            "authorityCheckSubPermissionName" => 'orderModifyShippingManage'
        ]);
        //修改订单金额
        Route::post('modifyMoney', 'modifyMoney')->append([
            "authorityCheckSubPermissionName" => 'orderModifyMoneyManage'
        ]);
        //取消订单
        Route::post('cancelOrder', 'cancelOrder')->append([
            "authorityCheckSubPermissionName" => 'cancelOrderManage'
        ]);
        //订单设置为已确认
        Route::post('setConfirm', 'setConfirm')->append([
            "authorityCheckSubPermissionName" => 'setConfirmManage'
        ]);
        //订单软删除
        Route::post('delOrder', 'delOrder')->append([
            "authorityCheckSubPermissionName" => 'delOrderManage'
        ]);
        //订单拆分
        Route::post('splitStoreOrder', 'splitStoreOrder')->append([
            "authorityCheckSubPermissionName" => 'splitStoreOrderManage'
        ]);
        //订单设置为已支付
        Route::post('setPaid', 'setPaid')->append([
            "authorityCheckSubPermissionName" => 'setPaidManage'
        ]);
        //修改商品信息
        Route::post('modifyProduct', 'modifyProduct')->append([
            "authorityCheckSubPermissionName" => 'modifyProductManage'
        ]);
        //添加商品时获取商品信息
        Route::post('getAddProductInfo', 'getAddProductInfo');
        //设置商家备注
        Route::post('setAdminNote', 'setAdminNote')->append([
            "authorityCheckSubPermissionName" => 'setAdminNoteManage'
        ]);
        //打印订单
        Route::get('orderPrint', 'orderPrint');
        //打印订单
        Route::get('orderPrintBill', 'orderPrintWaybill');
        //订单导出标签列表
        Route::get('getExportItemList', 'getExportItemList');
        //订单导出存的标签
        Route::post('saveExportItem', 'saveExportItem');
        //标签详情
        Route::get('exportItemInfo', 'exportItemInfo');
        //订单导出
        Route::get('orderExport', 'orderExport');
		// 批量操作
		Route::post('batch', 'batch')->append([
			"authorityCheckSubPermissionName" => 'splitStoreOrderManage'
		]);
		// 批量详情
		Route::get('severalDetail', 'severalDetail');
		// 批量打印
		Route::get('printSeveral', 'printSeveral');
        //物流信息
        Route::get('shippingInfo', 'shippingInfo');
        //获取订单列表配置
        Route::get('getOrderPageConfig', 'getOrderPageConfig');
        //修改订单状态
        Route::post('changeOrderStatus', 'changeOrderStatus');
    })->prefix("order.order/");
    //日志管理
    Route::group('orderLog', function () {
        // 列表
        Route::get('list', 'order.orderLog/list');
        // 添加日志
        Route::post('create', 'order.orderLog/create')->append([
            "authorityCheckSubPermissionName" => 'orderLogModifyManage'
        ]);
    });
    // 订单配置
    Route::group('config', function () {
        // 详情
        Route::get('detail', 'detail');
        // 修改
        Route::post('save', 'save')->append([
            "authorityCheckSubPermissionName" => 'orderConfigModifyManage'
        ]);
    })->prefix("order.config/");
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);