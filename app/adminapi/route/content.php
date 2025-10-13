<?php

use think\facade\Route;

// 文章组
Route::group('content', function () {
    // 文章管理
    Route::group('article', function () {
        // 文章列表
        Route::get('list', 'content.article/list');
        // 文章详情
        Route::get('detail', 'content.article/detail');
        // 文章添加
        Route::post('create', 'content.article/create')->append([
            "authorityCheckSubPermissionName" => 'articleModifyManage'
        ]);
        // 文章编辑
        Route::post('update', 'content.article/update')->append([
            "authorityCheckSubPermissionName" => 'articleModifyManage'
        ]);
        // 文章删除
        Route::post('del', 'content.article/del')->append([
            "authorityCheckSubPermissionName" => 'articleModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'content.article/updateField')->append([
            "authorityCheckSubPermissionName" => 'articleModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'content.article/batch')->append([
            "authorityCheckSubPermissionName" => 'articleModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'articleManage'
    ]);

    // 文章分类管理
    Route::group('articleCategory', function () {
        // 文章分类列表
        Route::get('list', 'content.articleCategory/list')->append([
            //用于权限校验的名称
            'authorityCheckAppendName' => 'articleCategoryManage'
        ]);
        // 文章分类详情
        Route::get('detail', 'content.articleCategory/detail');
        // 文章分类添加
        Route::post('create', 'content.articleCategory/create')->append([
            "authorityCheckSubPermissionName" => 'articleCategoryModifyManage'
        ]);
        // 文章分类编辑
        Route::post('update', 'content.articleCategory/update')->append([
            "authorityCheckSubPermissionName" => 'articleCategoryModifyManage'
        ]);
        // 文章分类删除
        Route::post('del', 'content.articleCategory/del')->append([
            "authorityCheckSubPermissionName" => 'articleCategoryModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'content.articleCategory/updateField')->append([
            "authorityCheckSubPermissionName" => 'articleCategoryModifyManage'
        ]);
        // 获取所有分类
        Route::get('tree', 'content.articleCategory/tree');
        // 批量操作
        Route::post('batch', 'content.articleCategory/batch')->append([
            "authorityCheckSubPermissionName" => 'articleCategoryModifyManage'
        ]);
    });
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);
