<?php

namespace app\service\admin\pay;

use app\model\payment\PayLog as PayLogModel;
use app\service\common\BaseService;
use exceptions\ApiException;
use Fastknife\Utils\RandomUtils;
use utils\Time;

class PayLogService extends BaseService
{

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter)->with(["order_info"])->append(["pay_status_name"]);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = PayLogModel::query();
        // 处理筛选条件

        if (isset($filter["keyword"]) && !empty($filter['keyword'])) {
            $query->where('order_sn', 'like', '%' . $filter['keyword'] . '%');
        }

        // 支付状态
        if (isset($filter["pay_status"]) && $filter["pay_status"] != -1) {
            $query->where('pay_status', $filter["pay_status"]);
        }

        // 订单检索
        if (isset($filter["order_id"]) && !empty($filter['order_id'])) {
            $query->where('order_id', $filter['order_id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 删除交易日志
     *
     * @param int $id
     * @return bool
     */
    public function deletePaylog(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = PayLogModel::destroy($id);
        return $result !== false;
    }
    /**
     * 获取支付状态
     * @param int $id
     * @return string
     */
    public function getPayStatus(int $id): string
    {
        return PayLogModel::where('paylog_id', $id)->value('pay_status');
    }

    /**
     * 获取唯一支付订单号
     * @return string
     */
    public function createPaySn(): string
    {
        $pay_sn = RandomUtils::getRandomCode(16);
        $pay_log_id = self::getPayLogId($pay_sn);
        while ($pay_log_id > 0) {
            self::createPaySn();
        }

        return $pay_sn;
    }

    /**
     * 创建支付日志参数
     * @param array $order
     * @return array
     */
    public function creatPayLogParams(array $order): array
    {
        $pay_sn = self::createPaySn();
        $params = [
            'pay_sn' => $pay_sn,
            'order_sn' => $order['order_sn'] ?? '订单支付',
            'pay_name' => $order['pay_code'], //支付名称'
            'pay_code' => $order['pay_code'],
            'token_code' => '',
            'order_id' => $order['order_id'],
        //    'order_amount' => $order['total_amount'],
            'total_amount' => $order['total_amount'],  //订单金额
            'order_amount' => $order['unpaid_amount'],  //改成未付款金额
            'unpaid_amount' => $order['unpaid_amount'],  //改成未付款金额
            'order_type' => $order['order_type'], //支付类型  0:普通订单   1:充值
        ];

        return $params;
    }

    /**
     * 创建充值日志参数
     * @param array $order
     * @return array
     */
    public function createRechargePayLogParams(array $order): array
    {
        $pay_sn = self::createPaySn();
        $params = [
            'pay_sn' => $pay_sn,
            'order_sn' => $order['order_sn'] ?? '订单支付',
            'pay_name' => $order['pay_code'], //支付名称'
            'pay_code' => $order['pay_code'],
            'token_code' => '',
            'order_id' => $order['order_id'],
            'order_amount' => $order['total_amount'],
            'order_type' => $order['order_type'],
        ];

        return $params;
    }


    /**
     * 插入支付日志
     * @param array $params
     * @return int
     */
    public function creatPayLog(array $params): int
    {
        //添加接口日志
        $data = [
            'order_id' => $params['order_id'],
            'pay_sn' => $params['pay_sn'],
            'pay_name' => $params['pay_name'] ?? '',
            'order_sn' => $params['order_sn'],
            'order_amount' => $params['total_amount'],
            'pay_amount' => $params['unpaid_amount'],
            'order_type' => $params['order_type'],
            'pay_code' => $params['pay_code'],
            'transaction_id' => $params['transaction_id'] ?? '',
            'token_code' => $params['token_code'] ?? '',
            'add_time' => Time::now(),
            'notify_data' => isset($params['notify_data']) ? json_encode($params['notify_data']) : ''
        ];

        return PayLogModel::insertGetId($data);
    }

    /**
     * 插入支付日志
     * @param array $params
     * @return int
     */
    public function createRechargePayLog(array $params): int
    {
        //添加接口日志
        $data = [
            'order_id' => $params['order_id'],
            'pay_sn' => $params['pay_sn'],
            'pay_name' => $params['pay_name'] ?? '',
            'order_sn' => $params['order_sn'],
            'order_amount' => $params['order_amount'],
            'pay_amount' => $params['order_amount'],
            'order_type' => $params['order_type'],
            'pay_code' => $params['pay_code'],
            'transaction_id' => $params['transaction_id'] ?? '',
            'token_code' => $params['token_code'] ?? '',
            'add_time' => Time::now(),
            'notify_data' => isset($params['notify_data']) ? json_encode($params['notify_data']) : ''
        ];

        return PayLogModel::insertGetId($data);
    }

    /**
     * 获取支付记录
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPayLog(int $id): array
    {
        return PayLogModel::where('paylog_id', $id)->findOrEmpty()->toArray();
    }

    /**
     * 获取支付记录
     * @param string $pay_sn
     * @return array
     */
    public function getPayLogByPaySn(string $pay_sn): array
    {
        return PayLogModel::where('pay_sn', $pay_sn)->findOrEmpty()->toArray();
    }

    /**
     * 获取支付日志id
     * @param string $pay_sn
     * @param string $token_code
     * @return int
     */
    public function getPayLogId(string $pay_sn = '', string $token_code = ''): int
    {
        if (empty($pay_sn) && empty($token_code)) return 0;
        if (!empty($pay_sn)) {
            $pay_log_id = PayLogModel::where('pay_sn', $pay_sn)->value('paylog_id');
        }
        if (!empty($token_code)) {
            $pay_log_id = PayLogModel::where('token_code', $token_code)->value('paylog_id');
        }

        return $pay_log_id ?? 0;
    }

    /**
     * 获取支付日志
     * @param int $order_id
     */
    public function getPayLogByOrderId(int $order_id)
    {
        //获得已支付的记录
        $pay_log = PayLogModel::where('order_id', $order_id)->where('pay_status', 1)->findOrEmpty();
        return $pay_log;
    }
}