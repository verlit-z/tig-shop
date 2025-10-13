<?php

namespace traits;

use think\facade\Env;
use think\Collection;
use think\Response;

trait OutputTrait
{
    // 普通逻辑层的格式输出
    protected function defaultOutput($data = '', $message = '', int $error_code = 0): Response
    {
        //判断$data是一个集合则转数组
        return json([
            'code' => $error_code,
            'message' => $message,
            'data' => camelCase($data),
        ]);
    }

    // 页面层的严重错误抛出格式，如代码错误、数据库错误等
    protected function fatalOutput($exception): Response
    {
        $app_debug = Env::get('app_debug');
        $message = $app_debug == false ? '请求错误，请稍后再试！' : $exception->getMessage();
        $code = $exception->getCode() > 0 ? $exception->getCode() : 500;
        if (!in_array($code, [200, 400, 401, 403, 500])) {
            $code = 500;
        }
        $arr = [
            'code' => $code,
            'message' => $message,
            'data' => null,
        ];
        if ($app_debug == true) {
            $arr['data']['file'] = $exception->getFile();
            $arr['data']['line'] = $exception->getLine();
            $arr['data']['trace'] = $exception->getTrace();
        }
        return json($arr, $code);
    }
}
