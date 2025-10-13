<?php

use think\facade\Route;

// 打印机管理模块
Route::group('print', function () {

    Route::group('print', function () {
        // 列表
        Route::post('create', 'create');
        Route::post('update', 'update');
        Route::post('del', 'delete');
        Route::post('updateField', 'updateField');
        Route::post('hasEnabled', 'hasEnabled');
        Route::get('list', 'list');
        Route::get('detail', 'detail');
    })->prefix("print.printer/");

    Route::group('printConfig', function () {
        Route::post('print', 'print');
        Route::post('update', 'update');
        Route::get('getConfigsByPrintId', 'getConfigsByPrintId');
    })->prefix("print.printConfig/");

});