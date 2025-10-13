<?php

namespace app\service\admin\order;

use app\model\order\OrderConfig;
use app\service\common\BaseService;
use exceptions\ApiException;

class OrderConfigService extends BaseService
{
    /**
     * 获取订单配置详情
     * @param string $code
     * @param int $shop_id
     * @return array|null
     */
    public function getDetail(string $code, int $shop_id = 0): ?array
    {
        $data = OrderConfig::where(['code' => $code, 'shop_id' => $shop_id])->findOrEmpty();
        return !empty($data['data']) ? $data['data'] : null;
    }

    /**
     * 保存订单配置
     * @param string $code
     * @param array $data
     * @param int $shop_id
     * @return bool
     * @throws ApiException
     */
    public function saveConfig(string $code, array $data, int $shop_id = 0): bool
    {
        if (empty($code)) {
            throw new ApiException(/** LANG */ '传参错误');
        }

        $config = OrderConfig::where(['code' => $code, 'shop_id' => $shop_id])->findOrEmpty();
        if ($config->isEmpty()) {
            // 新增配置
            OrderConfig::create(['code' => $code, 'shop_id' => $shop_id, 'data' => $data]);
        } else {
            $config->save(['data' => $data]);
        }
        return true;
    }
}