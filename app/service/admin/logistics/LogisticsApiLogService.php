<?php

namespace app\service\admin\logistics;

use app\model\logistics\LogisticsApiLog;
use app\service\common\BaseService;

class LogisticsApiLogService extends BaseService
{
    /**
     * 添加接口日志
     * @param int $order_id
     * @param string $order_code
     * @param string $logistic_code
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addLog(int $order_id, string $order_code, string $logistic_code,string $print_template = ''): bool
    {
        $res = LogisticsApiLog::where('logistic_code', $logistic_code)->find();
        if (!empty($res)) return true;
        $data = [
            'order_id' => $order_id,
            'order_code' => $order_code,
            'logistic_code' => $logistic_code,
            'print_template' => $print_template
        ];
        LogisticsApiLog::create($data);

        return true;
    }

    /**
     * 获取发送记录
     * @param string $logistic_code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail(string $logistic_code): array
    {
        $result = LogisticsApiLog::where('logistic_code', $logistic_code)->find();
        if (empty($result)) return [];

        return $result->toArray();
    }
}