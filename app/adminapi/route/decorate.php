<?php

use think\facade\Route;

// 装修组
Route::group('decorate', function () {
    // 装修管理
    Route::group('decorate', function () {
        // 装修列表
        Route::get('list', 'decorate.decorate/list')->append([
            //此处因为移动端装修权限和pc端装修权限单独设置但都是请求这个接口。所以不能在这拦截权限，需要代码控制器里控制
            // 'authorityCheckAppendName' => 'pcDecorate'
        ]);
        // 装修详情
        Route::get('detail', 'decorate.decorate/detail');
        // 草稿数据
        Route::get('loadDraftData', 'decorate.decorate/loadDraftData');
        // 存入草稿
        Route::post('saveDraft', 'decorate.decorate/saveDraft');
        // 发布
        Route::post('publish', 'decorate.decorate/publish')->append([
            "authorityCheckSubPermissionName" => 'decoratePublishManage'
        ]);
        // 复制
        Route::post('copy', 'decorate.decorate/copy');
        // 设置为首页
        Route::post('setHome', 'decorate.decorate/setHome')->append([
            "authorityCheckSubPermissionName" => 'decorateSetHomeManage'
        ]);
        // 装修添加
        Route::post('create', 'decorate.decorate/create');
        // 装修编辑
        Route::post('update', 'decorate.decorate/update');
        // 更新字段
        Route::post('updateField', 'decorate.decorate/updateField')->append([
            "authorityCheckSubPermissionName" => 'decorateModifyManage'
        ]);
        // 装修删除
        Route::post('del', 'decorate.decorate/del')->append([
            "authorityCheckSubPermissionName" => 'decorateModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'decorate.decorate/batch')->append([
            "authorityCheckSubPermissionName" => 'decorateModifyManage'
        ]);
    });

    //装修导入导出模块
    Route::group('decorateShare', function () {
        //装修分享
        Route::get('share', 'decorate.decorateShare/share');
        //装修导入
        Route::get('import', 'decorate.decorateShare/import');
    });

    // 装修模块管理
    Route::group('decorateDiscrete', function () {
        // 装修模块详情
        Route::get('detail', 'decorate.decorateDiscrete/detail');
        // 个人中心装修栏目列表
        Route::get('memberDecorateData', 'decorate.decorateDiscrete/memberDecorateData');
        // 装修模块编辑
        Route::post('update', 'decorate.decorateDiscrete/update')->append([
            "authorityCheckSubPermissionName" => 'decorateDiscreteModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'pcDecorateOtherManage'
    ]);
    // 装修异步请求
    Route::group('decorateRequest', function () {
        // 获取商品列表
        Route::get('productList', 'decorate.decorateRequest/productList');
        // 模块标识获取数据
        Route::get('decorateByModule', 'decorate.decorateRequest/decorateByModule');
    });
    // 首页分类栏
    Route::group('mobileCatNav', function () {
        // 首页分类栏列表
        Route::get('list', 'decorate.mobileCatNav/list');
        // 首页分类栏详情
        Route::get('detail', 'decorate.mobileCatNav/detail');
        // 首页分类栏添加
        Route::post('create', 'decorate.mobileCatNav/create')->append([
            "authorityCheckSubPermissionName" => 'mobileCatNavModifyManage'
        ]);
        // 首页分类栏编辑
        Route::post('update', 'decorate.mobileCatNav/update')->append([
            "authorityCheckSubPermissionName" => 'mobileCatNavModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'decorate.mobileCatNav/updateField')->append([
            "authorityCheckSubPermissionName" => 'mobileCatNavModifyManage'
        ]);
        // 首页分类栏删除
        Route::post('del', 'decorate.mobileCatNav/del')->append([
            "authorityCheckSubPermissionName" => 'mobileCatNavModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'decorate.mobileCatNav/batch')->append([
            "authorityCheckSubPermissionName" => 'mobileCatNavModifyManage'
        ]);
    });
    // 首页装修模板
    Route::group('mobileDecorate', function () {
        // 首页装修模板列表
        Route::get('list', 'decorate.mobileDecorate/list');
        // 首页装修模板详情
        Route::get('detail', 'decorate.mobileDecorate/detail');
        // 首页装修模板添加
        Route::post('create', 'decorate.mobileDecorate/create');
        // 首页装修模板编辑
        Route::post('update', 'decorate.mobileDecorate/update')->append([
            "authorityCheckSubPermissionName" => 'mobileDecorateModifyManage'
        ]);
        // 设置为首页
        Route::post('setHome', 'decorate.mobileDecorate/setHome')->append([
            "authorityCheckSubPermissionName" => 'mobileDecorateModifyManage'
        ]);
        // 复制
        Route::post('copy', 'decorate.mobileDecorate/copy');
        // 更新字段
        Route::post('updateField', 'decorate.mobileDecorate/updateField')->append([
            "authorityCheckSubPermissionName" => 'mobileDecorateModifyManage'
        ]);
        // 删除
        Route::post('del', 'decorate.mobileDecorate/del')->append([
            "authorityCheckSubPermissionName" => 'mobileDecorateModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'decorate.mobileDecorate/batch')->append([
            "authorityCheckSubPermissionName" => 'mobileDecorateModifyManage'
        ]);
    });
    // PC分类抽屉
    Route::group('pcCatFloor', function () {
        // PC分类抽屉列表
        Route::get('list', 'decorate.pcCatFloor/list');
        // PC分类抽屉详情
        Route::get('detail', 'decorate.pcCatFloor/detail');
        // PC分类抽屉添加
        Route::post('create', 'decorate.pcCatFloor/create')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
        // PC分类抽屉编辑
        Route::post('update', 'decorate.pcCatFloor/update')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'decorate.pcCatFloor/updateField')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
        // PC分类抽屉删除
        Route::post('del', 'decorate.pcCatFloor/del')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
        // 更新缓存
        Route::post('clearCache', 'decorate.pcCatFloor/clearCache')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'decorate.pcCatFloor/batch')->append([
            "authorityCheckSubPermissionName" => 'pcCatFloorModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'pcCatFloorManage'
    ]);
    // PC导航栏
    Route::group('pcNavigation', function () {
        // PC导航栏列表
        Route::get('list', 'decorate.pcNavigation/list');
        // PC导航栏详情
        Route::get('detail', 'decorate.pcNavigation/detail');
        // 获取上级导航
        Route::get('getParentNav', 'decorate.pcNavigation/getParentNav');
        // 选择链接地址
        Route::get('selectLink', 'decorate.pcNavigation/selectLink');
        // PC导航栏添加
        Route::post('create', 'decorate.pcNavigation/create')->append([
            "authorityCheckSubPermissionName" => 'pcNavigationModifyManage'
        ]);
        // PC导航栏编辑
        Route::post('update', 'decorate.pcNavigation/update')->append([
            "authorityCheckSubPermissionName" => 'pcNavigationModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'decorate.pcNavigation/updateField')->append([
            "authorityCheckSubPermissionName" => 'pcNavigationModifyManage'
        ]);
        // PC导航栏删除
        Route::post('del', 'decorate.pcNavigation/del')->append([
            "authorityCheckSubPermissionName" => 'pcNavigationModifyManage'
        ]);
        // 批量操作
        Route::post('batch', 'decorate.pcNavigation/batch')->append([
            "authorityCheckSubPermissionName" => 'pcNavigationModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendName' => 'pcNavigationManage'
    ]);
})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);
