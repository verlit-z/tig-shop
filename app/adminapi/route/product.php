<?php
use think\facade\Route;

// 商品模块
Route::group('product', function () {
    // 品牌
    Route::group('brand', function () {
        // 品牌列表
        Route::get('list', 'list');
        // 品牌详情
        Route::get('detail', 'detail');
        // 品牌添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        // 品牌编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        // 选择品牌
        Route::get('search', 'search');
        // 品牌删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        // 批量更新首字母
        Route::post('updateFirstWord', 'updateFirstWord')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);
        //获取品牌审核列表
        Route::get('auditList', 'auditList')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);

        //总后台审核店铺品牌
        Route::post('audit', 'audit')->append([
            "authorityCheckSubPermissionName" => 'brandModifyManage'
        ]);

        Route::get('auditWaitNum', 'auditWaitNum');

    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'brandManage'
    ])->prefix('product.brand/');

    // 分类
    Route::group('category', function () {
        // 列表
        Route::get('list', 'list');
        //获取父类名称
        Route::get('getParentName', 'getParentName');
        // 商品转移
        Route::post('moveCat', 'moveCat')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // 选择分类
        Route::get('getAllCategory', 'getAllCategory');
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productCategoryModifyManage'
        ]);
        // 详情
        Route::get('detail', 'detail');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'categoryManage'
    ])->prefix('product.category/');

    // 评论
    Route::group('comment', function () {
        // 列表
        Route::get('list', 'list');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // 回复评论
        Route::post('replyComment', 'replyComment')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'commentModifyManage'
        ]);
        // 详情
        Route::get('detail', 'detail');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'commentManage'
    ])->prefix('product.comment/');

    // 商品管理
    Route::group('product', function () {
        // 商品列表
        Route::get('list', 'list');
        //待审核商品数量
        Route::get('getWaitingCheckedCount', 'getWaitingCheckedCount');
        // 商品详情
        Route::get('detail', 'detail');
        // 商品新增
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 商品复制
        Route::post('copy', 'copy')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 商品配置型词典
        Route::get('config', 'config');
        // 商品编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 商品删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 商品分词
        Route::post('getParticiple', 'getParticiple');
        // 运费模板列表
        Route::get('shippingTplList', 'shippingTplList');
        // 更新字段
        Route::post('updateField', 'updateField');
        // 回收站
        Route::post('recycle', 'recycle')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        // 商品审核
        Route::post('audit', 'audit')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);;
        //审核不通过再次提交审核
        Route::post('auditAgain', 'auditAgain')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);
        //供应商商品导入
        Route::post('vendorProductImport','vendorProductImport')->append([
            "authorityCheckSubPermissionName" => 'productModifyManage'
        ]);
        //获取供应商最大价格
        Route::get('getVendorMaxPrice', 'getVendorMaxPrice');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productManage'
    ])->prefix('product.product/');

    // 商品分组
    Route::group('productGroup', function () {
        // 列表
        Route::get('list', 'list');
        // 编辑
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productGroupModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productGroupModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productGroupModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productGroupModifyManage'
        ]);
        // 详情
        Route::get('detail', 'detail');
    })->prefix('product.productGroup/')->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productGroupManage'
    ]);

    // 商品属性
    Route::group('productAttributes', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productAttributesGroupModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productAttributesGroupModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productAttributesGroupModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'productAttributesGroupModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productAttributesGroupModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productAttributesTplManage'
    ])->prefix('product.productAttributes/');

    // 商品属性模板
    Route::group('productAttributesTpl', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productAttributesTplModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productAttributesTplModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productAttributesTplModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'productAttributesTplModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productAttributesTplModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productAttributesTplManage'
    ])->prefix('product.productAttributesTpl/');

    // 商品库存日志
    Route::group('productInventoryLog', function () {
        // 列表
        Route::get('list', 'list');
        // 删除
//        Route::post('del', 'del')->append([
//            "authorityCheckSubPermissionName" => 'productInventoryLogModifyManage'
//        ]);
//        // batch批量操作
//        Route::post('batch', 'batch')->append([
//            "authorityCheckSubPermissionName" => 'productInventoryLogModifyManage'
//        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productInventoryLogManage'
    ])->prefix('product.productInventoryLog/');

    // 商品批量处理
    Route::group('productBatch', function () {
        // 商品批量导出
        Route::get('productBatchDeal', 'productBatchDeal');
        // 商品批量上传
        Route::post('productBatchModify', 'productBatchModify')->append([
            "authorityCheckSubPermissionName" => 'productBatchModifyManage'
        ]);
        // 批量修改商品
        Route::post('productBatchEdit', 'productBatchEdit')->append([
            "authorityCheckSubPermissionName" => 'productBatchEditManage'
        ]);
        // 下载模版文件
        Route::get('downloadTemplate', 'downloadTemplate');
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productBatch'
    ])->prefix('product.productBatch/');

    // 商品服务
    Route::group('productServices', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'productServicesModifyManage'
        ]);
        // 编辑
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'productServicesModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'productServicesModifyManage'
        ]);
        // 更新字段
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'productServicesModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'productServicesModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'productServicesManage'
    ])->prefix('product.productServices/');
    //电子卡券组
    Route::group('eCardGroup', function (){
        //列表
        Route::get('list', 'list');
        //添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //详情
        Route::get('detail', 'detail');
        //更新
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //更新
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //
        Route::get('cardList', 'cardList');
        //导入电子卡券
        Route::post('import', 'import')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
    })->append([

    ])->prefix('product.eCardGroup/');
    //电子卡券
    Route::group('eCard', function (){
        //列表
        Route::get('list', 'list');
        //添加
        Route::post('create', 'create')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //详情
        Route::get('detail', 'detail');
        //更新
        Route::post('update', 'update')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //更新
        Route::post('updateField', 'updateField')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
        //删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'eCardManage'
        ]);
    })->prefix('product.eCard/');

    // 商品询价
    Route::group('priceInquiry', function () {
        // 列表
        Route::get('list', 'list');
        // 详情
        Route::get('detail', 'detail');
        // 编辑
        Route::post('reply', 'reply')->append([
            "authorityCheckSubPermissionName" => 'priceInquiryModifyManage'
        ]);
        // 删除
        Route::post('del', 'del')->append([
            "authorityCheckSubPermissionName" => 'priceInquiryModifyManage'
        ]);
        // batch批量操作
        Route::post('batch', 'batch')->append([
            "authorityCheckSubPermissionName" => 'priceInquiryModifyManage'
        ]);
    })->append([
        //用于权限校验的名称
        'authorityCheckAppendGroupName' => 'priceInquiryManage'
    ])->prefix('product.priceInquiry/');

})->middleware([
    \app\adminapi\middleware\CheckAuthor::class
]);