<?php

namespace app\service\admin\profit;
abstract class ProfitSharingService
{
    /**
     * 请求分账
     * @param array $applyParams 申请信息
     * @param array $receivers 分账接收方列表
     * @param bool $IsUnfreezeUnsplit 是否解冻剩余资金
     * @return array
     */
    abstract public function apply(array $applyParams, array $receivers, bool $IsUnfreezeUnsplit): array;

    /**
     * 查询分账结果
     * @param array $queryParams
     * @return array
     */
    abstract public function queryApply(array $queryParams): array;

    /**
     * 请求分账回退
     * @param array $returnParams
     * @return array
     */
    abstract public function returnApply(array $returnParams): array;

    /**
     * 查询分账回退结果
     * @param array $queryParams
     * @return array
     */
    abstract public function queryReturnApply(array $queryParams): array;

    /**
     * 解冻剩余资金
     * @param array $queryParams
     * @return array
     */
    abstract public function unfreeze(array $queryParams): array;

    /**
     * 查询剩余待分金额
     * @param array $queryParams
     * @return array
     */
    abstract public function queryResidueProfit(array $queryParams): array;

    /**
     * 添加分账接收方
     * @param array $receivers
     * @return array
     */
    abstract public function addReceivers(array $receivers): array;

    /**
     * 删除分账接收方
     * @param array $receivers
     * @return array
     */
    abstract public function deleteReceivers(array $receivers): array;
}