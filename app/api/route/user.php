<?php

use think\facade\Route;

// 会员中心
Route::group('user', function () {
    // 账户明细
    Route::group('account', function () {
        // 账户金额变动列表
        Route::get('list', 'user.account/list');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 收货地址
    Route::group('address', function () {
        // 收货地址列表
        Route::get('list', 'user.address/list');
        // 收货地址详情
        Route::get('detail', 'user.address/detail');
        // 收货地址添加
        Route::post('create', 'user.address/create');
        // 收货地址更新
        Route::post('update', 'user.address/update');
        // 收货地址删除
        Route::post('del', 'user.address/del');
        // 设为选中
        Route::post('setSelected', 'user.address/setSelected');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 售后
    Route::group('aftersales', function () {
        // 可售后订单列表
        Route::get('list', 'user.aftersales/list');
        // 配置型
        Route::get('config', 'user.aftersales/config');
        // 售后详情
        Route::get('applyData', 'user.aftersales/applyData');
        // 售后申请
        Route::post('create', 'user.aftersales/create');
        // 售后申请修改
        Route::post('update', 'user.aftersales/update');
        // 售后申请记录
        Route::get('getRecord', 'user.aftersales/getRecord');
        // 查看售后记录
        Route::get('detail', 'user.aftersales/detail');
        // 查看售后log记录
        Route::get('detailLog', 'user.aftersales/detailLog');
        // 提交售后反馈记录
        Route::post('feedback', 'user.aftersales/feedback');
        // 撤销申请售后
        Route::post('cancel', 'user.aftersales/cancel');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 商品收藏
    Route::group('collectProduct', function () {
        // 商品收藏列表
        Route::get('list', 'user.collectProduct/list');
        // 收藏商品
        Route::post('save', 'user.collectProduct/save');
        // 取消收藏
        Route::post('cancel', 'user.collectProduct/cancel');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 评论晒单
    Route::group('comment', function () {
        // 评论晒单数量
        Route::get('subNum', 'user.comment/subNum');
        // 晒单列表
        Route::get('showedList', 'user.comment/showedList');
        // 已评价列表
        Route::get('list', 'user.comment/list');
        // 商品评价 / 晒单
        Route::post('evaluate', 'user.comment/evaluate');
        // 评价/晒单详情
        Route::get('detail', 'user.comment/detail');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 优惠券
    Route::group('coupon', function () {
        // 会员优惠券列表
        Route::get('list', 'user.coupon/list')->middleware([
            \app\api\middleware\CheckLogin::class,
        ]);
        // 删除优惠券
        Route::post('del', 'user.coupon/del')->middleware([
            \app\api\middleware\CheckLogin::class,
        ]);
        // 优惠券列表
        Route::get('getList', 'user.coupon/getList');
        // 领取优惠券
        Route::post('claim', 'user.coupon/claim')->middleware([
            \app\api\middleware\CheckLogin::class,
        ]);
        // 优惠券详情
        Route::get('detail', 'user.coupon/detail')->middleware([
            \app\api\middleware\CheckLogin::class,
        ]);
    });
    // 留言咨询
    Route::group('feedback', function () {
        // 订单咨询/留言列表
        Route::get('list', 'user.feedback/list');
        // 提交留言
        Route::post('submit', 'user.feedback/submit');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 增票资质发票
    Route::group('invoice', function () {
        // 详情
        Route::get('detail', 'user.invoice/detail');
        // 添加
        Route::post('create', 'user.invoice/create');
        // 更新
        Route::post('update', 'user.invoice/update');
        // 判断当前用户的增票资质是否审核通过
        Route::get('getStatus', 'user.invoice/getStatus');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 登录
    Route::group('login', function () {
        //快捷登录设置项目
        Route::get('getQuickLoginSetting', 'getQuickLoginSetting');
        // 登录
        Route::post('signin', 'signin');
        // 获取验证码
        Route::post('sendMobileCode', 'sendMobileCode');
        // 验证手机号
        Route::post('checkMobile', 'checkMobile');
        //验证邮箱
        Route::post('checkEmail', 'checkEmail');
        // 忘记密码 -- 修改密码
        Route::post('forgetPassword', 'forgetPassword');
        // 获得微信授权url
        Route::get('getWxLoginUrl', 'getWechatLoginUrl');
        // 通过微信code获得微信用户信息
        Route::get('getWxLoginInfoByCode', 'getWechatLoginInfoByCode');
        //第三方绑定手机号
        Route::post('bindMobile', 'bindMobile');
        //绑定微信公众号
        Route::post('bindWechat', 'bindWechat')->middleware([\app\api\middleware\CheckLogin::class]);
        //解除绑定微信公众号
        Route::get('unbindWechat', 'unbindWechat')->middleware([\app\api\middleware\CheckLogin::class]);
        //微信服务器校验
        Route::get('wechatServer', 'wechatServerVerify');
        //获取微信推送消息
        Route::post('wechatServer', 'getWechatMessage');
        //检测微信用户操作事件
        Route::post('wechatEvent', 'wechatEvent');
        //获取手机号
        Route::post('getMobile', 'getUserMobile');
        //获取用户openid
        Route::post('updateUserOpenId', 'updateUserOpenId')->middleware([\app\api\middleware\CheckLogin::class]);
        //获取jssdk配置项
        Route::post('getJsSdkConfig', 'getJsSdkConfig')->middleware([\app\api\middleware\CheckLogin::class]);
        //获取邮箱验证码
        Route::post('sendEmailCode', 'sendEmailCode');
    })->prefix("user.login/");
    // 站内信
    Route::group('message', function () {
        // 站内信列表
        Route::get('list', 'user.message/list');
        // 全部标记已读
        Route::post('updateAllRead', 'user.message/updateAllRead');
        // 设置站内信已读
        Route::post('updateMessageRead', 'user.message/updateMessageRead');
        // 删除站内信
        Route::post('del', 'user.message/del');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);

    // 订单
    Route::group('order', function () {
        // 列表
        Route::get('list', 'user.order/list');
        // 详情
        Route::get('detail', 'user.order/detail');
        // 数量
        Route::get('orderNum', 'user.order/orderNum');
        // 取消
        Route::post('cancelOrder', 'user.order/cancelOrder');
        // 删除
        Route::post('delOrder', 'user.order/delOrder');
        // 收货
        Route::post('confirmReceipt', 'user.order/confirmReceipt');
        // 物流信息
        Route::get('shippingInfo', 'user.order/shippingInfo');
        // 再次购买
        Route::post('buyAgain', 'user.order/buyAgain');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 订单发票
    Route::group('orderInvoice', function () {
        //详情
        Route::get('detail', 'detail');
        // 新增
        Route::post('create', 'create');
        // 编辑
        Route::post('update', 'update');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ])->prefix("user.order_invoice/");
    // 积分
    Route::group('pointsLog', function () {
        // 列表
        Route::get('list', 'user.pointsLog/list');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 充值
    Route::group('rechargeOrder', function () {
        // 列表
        Route::get('list', 'user.rechargeOrder/list');
        // 充值申请
        Route::post('update', 'user.rechargeOrder/update');
        // 充值金额列表
        Route::get('setting', 'user.rechargeOrder/setting');
        // 充值支付列表
        Route::get('paymentList', 'user.rechargeOrder/paymentList');
        // 充值支付
        Route::post('pay', 'user.rechargeOrder/pay');
        // 充值支付
        Route::post('create', 'user.rechargeOrder/create');
        // 获取充值支付状态
        Route::get('checkStatus', 'user.rechargeOrder/checkStatus');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 会员注册
    Route::group('regist', function () {
        // 会员注册操作
        Route::post('registAct', 'registAct');
		// 邮箱验证码
        Route::post('sendEmailCode', 'sendEmailCode');
    })->prefix("user.regist/");
    // 会员
    Route::group('user', function () {
        // 会员详情
        Route::get('detail', 'detail');
        // 修改个人信息
        Route::post('updateInformation', 'updateInformation');
        // 会员中心首页数据
        Route::get('memberCenter', 'memberCenter');
        // 授权回调获取用户信息
        Route::post('oAuth', 'oAuth');
        // 修改密码获取验证码
        Route::post('sendMobileCodeByModifyPassword', 'sendMobileCodeByModifyPassword');
        // 修改密码手机验证
        Route::post('checkModifyPasswordMobileCode', 'checkModifyPasswordMobileCode');
        // 修改密码
        Route::post('modifyPassword', 'modifyPassword');
        // 手机修改获取验证码
        Route::post('sendMobileCodeByMobileValidate', 'sendMobileCodeByMobileValidate');
        //邮箱修改获取验证码
        Route::post('sendEmailCodeByEmailValidate', 'sendEmailCodeByEmailValidate');
        // 手机修改新手机获取验证码
        Route::post('sendMobileCodeByModifyMobile', 'sendMobileCodeByModifyMobile');
        // 邮箱修改新邮箱获取验证码
        Route::post('sendEmailCodeByModifyEmail', 'sendEmailCodeByModifyEmail');
        // 手机绑定
        Route::post('modifyMobile', 'modifyMobile');
        //邮箱绑定
        Route::post('modifyEmail', 'modifyEmail');
        // 手机验证
        Route::post('mobileValidate', 'mobileValidate');
        // 邮箱验证
        Route::post('emailValidate', 'emailValidate');

        Route::post('emailValidateNew', 'emailValidateNew');
        // 最近浏览
        Route::get('historyProduct', 'historyProduct');
        // 最近浏览
        Route::post('delHistoryProduct', 'delHistoryProduct');
        // 上传文件接口
        Route::post('uploadImg', 'uploadImg');
        // 修改头像
        Route::post('modifyAvatar', 'modifyAvatar');
        // 我收藏的
        Route::get('collectionShop', 'myCollectShop');
        // 会员等级列表
        Route::get('levelList', 'levelList');
        //会员权益信息
        Route::get('levelInfo', 'levelInfo');
        //退出登陆
        Route::post('logout', 'logout');
        //用户注销
        Route::post('close', 'close');
        //获取用户openid
        Route::get('userOpenId', 'userOpenId');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ])->prefix("user.user/");
    // 提现
    Route::group('withdrawApply', function () {
        // 列表
        Route::get('list', 'user.withdrawApply/list');
        // 添加提现账号
        Route::post('createAccount', 'user.withdrawApply/createAccount');
        // 编辑提现账号
        Route::post('updateAccount', 'user.withdrawApply/updateAccount');
        // 提现账号详情
        Route::get('accountDetail', 'user.withdrawApply/accountDetail');
        // 删除提现账号
        Route::post('delAccount', 'user.withdrawApply/delAccount');
        // 提现申请
        Route::post('apply', 'user.withdrawApply/apply');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);
    // 签到
    Route::group('sign', function () {
        // 账户金额变动列表
        Route::get('index', 'user.sign/index');
        Route::get('sign', 'user.sign/signIn');
    })->middleware([
        \app\api\middleware\CheckLogin::class,
    ]);

	// 会员企业认证
	Route::group('company', function () {
		// 申请企业认证
		Route::post('apply', 'apply');
		// 认证详情
		Route::get('detail', 'detail');
		// 我的申请
        Route::get('myApply', 'myApply');
	})->middleware([
		\app\api\middleware\CheckLogin::class,
	])->prefix("user.company/");


    // 第三方登录
    Route::group('oauth', function () {
        //
        // 获取系统配置
        Route::get('render/:source', 'render');
        // 获得所有省份接口
        Route::post('callback/:source', 'callback');

    })->prefix("user.oauth/");

});