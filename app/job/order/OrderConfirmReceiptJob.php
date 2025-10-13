<?php

namespace app\job\order;

use app\job\BaseJob;
use app\service\admin\order\AftersalesService;
use app\service\admin\order\OrderService;
use think\Log;
use utils\Config;
use utils\TigQueue;

class OrderConfirmReceiptJob extends BaseJob
{
    /**
     * 自动收货
     */
    public function doJob(array $data)
    {

        //有售后的订单延时处理
        $after = app(AftersalesService::class)->checkHasProcessingAfterSale($data['order_id']);
        if ($after>0){
            //有售后的订单延时添加自动收货逻辑
            $autoDeliveryDays = Config::get('autoDeliveryDays');
            if(!empty($autoDeliveryDays) && $autoDeliveryDays > 0) {
                //触发
                $days = ceil($autoDeliveryDays * 86400);
                app(TigQueue::class)->later(OrderConfirmReceiptJob::class, $days,
                    ['order_id' => $data['order_id']]);
            }
            return true;
        }else{
            //只处理已发货的
            $order = app(OrderService::class)->getOrder($data['order_id']);

            if($order->order_status == 2) {
                app(OrderService::class)->confirmReceipt($data['order_id']);
            }
            return true;
        }


    }
}