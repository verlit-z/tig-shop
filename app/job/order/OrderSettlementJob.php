<?php

namespace app\job\order;

use app\job\BaseJob;
use app\service\admin\merchant\ShopService;

class OrderSettlementJob extends BaseJob
{
    /**
     * 订单收货后结算店铺金额
     * @param $data
     * @return bool
     */
    public function doJob($data): bool
    {
        try {
            app(ShopService::class)->autoShopAccountByOrder($data);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}