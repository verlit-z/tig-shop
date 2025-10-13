<?php

namespace log;

use utils\Time;

class OrderLog
{
    /**
     * 添加管理员操作日志
     *
     * @param string $message
     * @param string $app
     */
    public static function add(string $order_id,int $order_sn, string $message = '')
    {
        $data = [
            'order_id' => $order_id,
            'order_sn' => $order_sn,
            'log_time' => Time::now(),
            'user_id' => request()->userId > 0 ? request()->userId : 0,
            'admin_id' => request()->adminUid > 0 ? request()->adminUid : 0,
            'description' => stripslashes($message),
        ];
		\app\model\order\OrderLog::create($data);
        // 如果是后台操作
        if(request()->adminUid > 0){
            AdminLog::add('订单：'.$order_sn.'，'.$message);
        }
    }
}
