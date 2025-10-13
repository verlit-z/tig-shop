<?php

namespace app\service\admin\logistics;
abstract class LogisticsService
{
    /**
     * 获取物流轨迹
     * @param array $params
     * @return array
     */
    abstract public function track(array $params): array;

    /**
     * 获取电子面单
     * @param array $order 订单信息
     * @param string $remark 发货备注
     * @return array
     */
    abstract public function getElectronicWaybill(array $order, string $remark = ''): array;

    /**
     * 取消电子面单
     * @param array $order
     * @return bool
     */
    abstract public function cancelElectronicWaybill(array $order): bool;
}