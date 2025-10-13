<?php

use think\facade\Route;

Route::group('product', function () {

    //兑换
    Route::group('exchange', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 详情
        Route::post('addToCart', 'addToCart')->middleware([
            \app\api\middleware\CheckLogin::class,
        ]);;

    })->prefix("product.exchange/");

    // 商品
    Route::group('product', function () {
        // 详情
        Route::get('detail', 'detail');
        // 评论
        Route::get('getComment', 'getComment');
        // 评论列表
        Route::get('getCommentList', 'getCommentList');
        // 咨询列表
        Route::get('getFeedbackList', 'getFeedbackList');
        // 可用信息sku和活动等
        Route::get('getProductAvailability', 'getProductAvailability');
        // 批量可用信息sku和活动等
        Route::get('getBatchProductAvailability', 'getBatchProductAvailability');
        // 批量获得商品最终价格
        Route::post('getPriceInBatches', 'getPriceInBatches');

        Route::post('getProductAmount', 'getProductAmount');
        // 列表
        Route::get('list', 'list');
        // 优惠卷
        Route::get('getCoupon', 'getCouponList');
        // 是否收藏
        Route::get('isCollect', 'isCollect');
        // 优惠信息
        Route::post('promotion', 'getProductsPromotion');
        // 是否收藏
        Route::get('getRelated', 'getProductRelated');

    })->prefix("product.product/");
});