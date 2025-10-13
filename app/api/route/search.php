<?php
use think\facade\Route;

// 搜索
Route::group('search', function () {
    // 搜索
    Route::group('search', function () {
        // 获取筛选列表
        Route::get('getFilter', 'search.search/getFilter');
        // 获取筛选商品列表
        Route::get('getProduct', 'search.search/getProduct');
    });
    // 关键词搜索
    Route::group('searchGuess', function () {
        // 获取关键词搜索列表
        Route::get('index', 'search.searchGuess/index');
    });
});