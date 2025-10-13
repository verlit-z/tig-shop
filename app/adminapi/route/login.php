<?php

use think\facade\Route;

// 登录
Route::group('login', function () {
    Route::post('signin', 'login.login/signin'); //登录
    Route::post('sendMobileCode', 'login.login/sendMobileCode'); // 获取验证码
});