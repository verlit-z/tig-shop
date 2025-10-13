<?php

namespace app\middleware;

use exceptions\ApiException;
use utils\Time;

class Reptiles
{
    /**
     * 防止爬虫
     * @param $request
     * @param \Closure $next
     * @return object|mixed
     * @throws ApiException
     */
    public function handle($request, \Closure $next): object
    {
        $ApiSecret = $request->header('Secret');

//        if (empty($ApiSecret)) {
//            throw new ApiException('Request Error!');
//        }
//        try {
//            $key = env('API_KEY');
//            if (!empty($key)) {
//                $decrypted = openssl_decrypt(base64_decode($ApiSecret), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
//                if (empty($decrypted)) throw new ApiException('Request Error!');
//                //解密时间与当前时间差值
//                $current_time = Time::now();
//                if ($current_time - $decrypted > 3) {
//                    throw new ApiException('Request Timeout!');
//                }
//            }
//        } catch (\Exception $exception) {
//            throw new ApiException('Request Error!');
//        }

        $response = $next($request);
        return $response;
    }
}