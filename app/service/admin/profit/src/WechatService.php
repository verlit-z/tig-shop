<?php

namespace app\service\admin\profit\src;

use app\service\admin\pay\src\WechatPayService;
use app\service\admin\profit\ProfitSharingService;
use exceptions\ApiException;

class WechatService extends ProfitSharingService
{
    /**
     * 请求分账
     * @param array $applyParams ['appid' => '支付的APPID','transaction_id' => '微信支付单号','out_order_no' => '商城订单号']
     * @param array $receivers
     * @param bool $IsUnfreezeUnsplit
     * @return array
     * @throws ApiException
     */
    public function apply(array $applyParams, array $receivers, bool $IsUnfreezeUnsplit): array
    {
        try {
            $response = $this->getApplication()->getClient()->postJson('/v3/profitsharing/orders', $this->getApplyData($order, $receivers, $IsUnfreezeUnsplit));
            $res = $response->toArray(false);
            if (isset($res['state']) && empty($res['state'])) {
                return $res;
            } else {
                //处理失败
                throw new ApiException($res['message']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 查询分账结果
     * @param array $queryParams ['order_sn' => '商城订单号','transaction_id' => '微信支付单号']
     * @return array
     * @throws ApiException
     */
    public function queryApply(array $queryParams): array
    {
        try {
            $response = $this->getApplication()->getClient()->get('/v3/profitsharing/orders/' . $queryParams['order_sn'], [
                'query' => [
                    'transaction_id' => $queryParams['transaction_id'],
                ],
            ]);
            $res = $response->toArray(false);
            if (isset($res['state']) && empty($res['state'])) {
                return $res;
            } else {
                //处理失败
                throw new ApiException($res['message']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    public function returnApply(array $returnParams): array
    {
        return [];
    }

    public function queryReturnApply(array $queryParams): array
    {
        return [];
    }

    /**
     * 解冻剩余资金 ['transaction_id' => '微信订单号','order_sn' => '商户分账单号','description' => '分账描述--必填']
     * @param array $queryParams
     * @return array
     * @throws ApiException
     */
    public function unfreeze(array $queryParams): array
    {
        try {
            $response = $this->getApplication()->getClient()->postJson('/v3/profitsharing/orders/unfreeze', $this->getUnfreezeData($queryParams));
            $res = $response->toArray(false);
            if (isset($res['state']) && empty($res['state'])) {
                return $res;
            } else {
                //处理失败
                throw new ApiException($res['message']);
            }
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 查询剩余待分金额 ['transaction_id' => '微信订单号']
     * @param array $queryParams
     * @return array
     * @throws ApiException
     */
    public function queryResidueProfit(array $queryParams): array
    {
        try {
            $response = $this->getApplication()->getClient()->get('/v3/profitsharing/transactions/' . $queryParams['transaction_id'] . '/amounts');
            return $response->toArray(false);
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 添加分账接收方
     * @param array $receivers ['appid' => '微信分配的服务商appid','type' => '商户类型 MERCHANT_ID，PERSONAL_OPENID','account' => '分账接收方帐号','name' => '分账接收方名称','relation_type' => '与分账方的关系类型']
     * @return array
     * @throws ApiException
     */
    public function addReceivers(array $receivers): array
    {
        try {
            $response = $this->getApplication()->getClient()->postJson('/v3/profitsharing/receivers/add', $receivers);

            return $response->toArray(false);
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 删除分账接收方
     * @param array $receivers ['appid' => '微信分配的服务商appid','type' => '商户类型 MERCHANT_ID，PERSONAL_OPENID','account' => '分账接收方帐号']
     * @return array
     * @throws ApiException
     */
    public function deleteReceivers(array $receivers): array
    {
        try {
            $response = $this->getApplication()->getClient()->postJson('/v3/profitsharing/receivers/delete', $receivers);

            return $response->toArray(false);
        } catch (\Exception $exception) {
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 获取微信支付实例
     * @return object|\EasyWeChat\Pay\Application
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function getApplication(): object
    {
        return app(WechatPayService::class)->getApplication();
    }

    /**
     * 获取分账请求参数
     * @param array $order
     * @param array $receivers
     * @param bool $IsUnfreezeUnsplit
     * @return array
     */
    public function getApplyData(array $order, array $receivers, bool $IsUnfreezeUnsplit): array
    {
        return [
            'appid' => $order['appid'],//微信分配的服务商appid
            'transaction_id' => $order['transaction_id'],//微信支付订单号
            'out_order_no' => $order['order_sn'],//商户分账单号
            'receivers' => $receivers, //分账接收方列表
            'unfreeze_unsplit' => $IsUnfreezeUnsplit //是否解冻剩余未分资金
        ];
    }

    /**
     * 获取解冻资金参数
     * @param array $queryParams
     * @return array
     */
    public function getUnfreezeData(array $queryParams): array
    {
        return [
            'transaction_id' => $queryParams['transaction_id'],
            'out_order_no' => $queryParams['order_sn'],
            'description' => $queryParams['description']
        ];
    }
}