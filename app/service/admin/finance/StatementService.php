<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 对账单服务类
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\finance;

use app\model\finance\Statement;
use app\model\finance\StatementDownload;
use app\model\merchant\Shop;
use app\model\merchant\ShopWithDraw;
use app\model\order\Order;
use app\model\vendor\Vendor;
use app\model\vendor\VendorWithdraw;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Config;
use utils\Excel;
use utils\Time;
use utils\Util;

/**
 * 对账单服务类
 */
class StatementService extends BaseService
{
    public function __construct()
    {
        $this->model = new Statement();
    }


    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
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
        $query = $this->model->query();
        // 处理筛选条件

        if (isset($filter["keyword"]) && !empty($filter['keyword'])) {
            $query->where('record_sn', 'like', "%{$filter["keyword"]}%");
        }

        if (isset($filter["record_sn"]) && !empty($filter['record_sn'])) {
            $query->where('record_sn', $filter["record_sn"]);
        }

        if (isset($filter["record_id"]) && $filter["record_id"] > 0) {
            $query->where('record_id', $filter["record_id"]);
        }

        if (isset($filter["shop_id"]) && $filter["shop_id"] > 0) {
            $query->where('shop_id', $filter["shop_id"]);
        }

        if (isset($filter["vendor_id"]) && $filter["vendor_id"] > 0) {
            $query->where('vendor_id', $filter["vendor_id"]);
        }

        if (isset($filter["account_type"]) && $filter["account_type"] > 0) {
            $query->where('account_type', $filter["account_type"]);
        }

        if (isset($filter["type"]) && $filter["type"] > 0) {
            $query->where('type', $filter["type"]);
        }

//        if (isset($filter["entry_type"]) && $filter["entry_type"] > 0) {
//            $query->where('type', $filter["type"]);
//        }

        if (isset($filter["payment_type"]) && !empty($filter["payment_type"])) {
            $query->where('payment_type', $filter["payment_type"]);
        }

        if (isset($filter["start_settlement_time"]) && $filter["start_settlement_time"] > 0) {
            $query->where('settlement_time', '>=', $filter["start_settlement_time"]);
        }

        if (isset($filter["end_settlement_time"]) && $filter["end_settlement_time"] > 0) {
            $query->where('settlement_time', '<=', $filter["end_settlement_time"]);
        }
        if (isset($filter["start_record_time"]) && $filter["start_record_time"] > 0) {
            $query->where('record_time', '>=', $filter["start_record_time"]);
        }
        if (isset($filter["end_record_time"]) && $filter["end_record_time"] > 0) {
            $query->where('record_time', '<=', $filter["end_record_time"]);
        }

        if (isset($filter["start_date_time"]) && !empty($filter["start_date_time"])) {
            $query->where('gmt_create', '>=', $filter["start_date_time"]);
        }

        if (isset($filter["end_date_time"]) && !empty($filter["end_date_time"])) {
            $query->where('gmt_create', '<=', $filter["end_date_time"]);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        return $query;
    }


    /**
     * 导出对账单
     *
     * @param array $filter
     * @return bool
     */
    public function exportStatement(array $filter):bool
    {

        $query = $this->filterQuery($filter);
        $result = $query->select()->toArray();


        $shopIds=array_unique(array_column($result, 'shop_id'));
        $shopName=Shop::whereIn('shop_id', $shopIds)->column('shop_title', 'shop_id');


        $list=[];

        if (!empty($result)){
            foreach ($result as $key => $item) {
                $list[$key]['shop_name'] = $shopName[$item['shop_id']] ?? '';
                $list[$key]['record_sn'] = $item['record_sn'] ?? '';
                $list[$key]['vendor_name'] = $vendorName[$item['vendor_id']] ?? '';
                $list[$key]['account_type_name'] = Statement::getAccountTypeName($item['account_type']);
                $list[$key]['type_name'] = Statement::getStatementTypeName($item['type']);
                $list[$key]['entry_type_name'] = Statement::getEntryTypeName($item['entry_type']);
                $list[$key]['payment_type_name'] = Statement::getPayTypeName($item['payment_type']);
                $list[$key]['account_balance'] = $item['account_balance'] ?? 0;
                $list[$key]['amount'] = $item['amount'] ?? 0;
                $list[$key]['record_time'] = $item['gmt_create'] ?? '';
                $list[$key]['settlement_time'] = $item['settlement_time'] ?? '';
            }
        }
        $export_title=[
            '店铺名称',
            '单据号',
            '供应商名称',
            '账户类型名称',
            '类型名称',
            '入账方式名称',
            '支付方式名称',
            '账户余额',
            '交易金额',
            '交易时间',
            '入账时间',
        ];

        $file_name = "对账单导出" . Time::getCurrentDatetime("Ymd") . rand(1000, 9999);
        Excel::export($export_title, $file_name, $list);
        return true;
    }


    public function exportStatementStatistics(array $filter):bool
    {
        $dateComponentType=Statement::getDateComponentType($filter['statement_date_type']);
        if (empty($dateComponentType)){
            throw new ApiException('日期类型错误');
        }

        $type_name =Statement::getAccountTypeName($filter['account_type']);
        $result=[];
        switch ($filter['statement_date_type']){

            case Statement::DAY:
                $day = explode("-", $filter['statement_date']);
                if (count($day) != 3) {
                    throw new ApiException('日期格式错误');
                }
                $income = Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('statement_day', $day[2])
                    ->where('amount', '>', 0)
                    ->field(['statement_day', 'sum(amount) as amount', 'count(1) as num'])
                    ->find();

                $expenditure = Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('statement_day', $day[2])
                    ->where('amount', '<', 0)
                    ->field(['statement_day', 'sum(amount) as amount', 'count(1) as num'])
                    ->find();

                $result = [
                    [
                        'statement_date' => $filter['statement_date'],
                        'income' => $income['amount'] ?? 0,
                        'expenditure' => $expenditure['amount'] ?? 0,
                        'income_count' => $income['num'] ?? 0,
                        'expenditure_count' => $expenditure['num'] ?? 0,
                        'account_type' => $type_name,
                    ]
                ];
                break;
            case Statement::MONTH:
                $day = explode("-", $filter['statement_date']);
                if (count($day) != 2) {
                    throw new ApiException('日期格式错误');
                }
                $income= Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('amount', '>',0)
                    ->field(['statement_month','sum(amount) as amount','count(1) as num'])
                    ->find();

                $expenditure= Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('amount', '<',0)
                    ->field(['statement_month','sum(amount) as amount','count(1) as num'])
                    ->group('statement_month')
                    ->find();

                $result = [
                    [
                        'statement_date' => $filter['statement_date'],
                        'income' => $income['amount'] ?? 0,
                        'expenditure' => $expenditure['amount'] ?? 0,
                        'income_count' => $income['num'] ?? 0,
                        'expenditure_count' => $expenditure['num'] ?? 0,
                        'account_type' => $type_name,
                    ]
                ];
                break;
            case Statement::YEAR:
                $income= Statement::where('statement_year', $filter['statement_date'])
                    ->where('amount', '>',0)
                    ->field(['statement_year','sum(amount) as amount','count(1) as num'])
                    ->find();

                $expenditure= Statement::where('statement_year', $filter['statement_date'])
                    ->where('amount', '<',0)
                    ->field(['statement_year','sum(amount) as amount','count(1) as num'])
                    ->find();
                $result = [
                    [
                        'statement_date' => $filter['statement_date'],
                        'income' => $income['amount'] ?? 0,
                        'expenditure' => $expenditure['amount'] ?? 0,
                        'income_count' => $income['num'] ?? 0,
                        'expenditure_count' => $expenditure['num'] ?? 0,
                        'account_type' => $type_name,
                    ]
                ];
                break;
        }

        $export_title=[
            '日期',
            '收入金额',
            '支出金额',
            '收入笔数',
            '支出笔数',
            '账户类型备注',
        ];

        $file_name = "对账单导出" . Time::getCurrentDatetime("Ymd") . rand(1000, 9999);
        Excel::export($export_title, $file_name, $result);
        return true;
    }


    /**
     * 获取对账单统计列表
     * @param array $filter
     * @return array
     */
    public function getStatementStatisticsList(array $filter):array
    {

        $dateComponentType=Statement::getDateComponentType($filter['statement_date_type']);
        if (empty($dateComponentType)){
            throw new ApiException('日期类型错误');
        }

        $type_name =Statement::getAccountTypeName($filter['account_type']);
        $result=[];
        switch ($filter['statement_date_type']){

            case Statement::DAY:
                $monthDays =Time::getMonthDays($filter['statement_date']);
                $day = explode("-", $filter['statement_date']);
                $income= Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('amount', '>',0)
                    ->field(['statement_day','sum(amount) as amount','count(1) as num'])
                    ->group('statement_day')
                    ->select()->toArray();

                $expenditure= Statement::where('statement_year', $day[0])
                    ->where('statement_month', $day[1])
                    ->where('amount', '<',0)
                    ->field(['statement_day','sum(amount) as amount','count(1) as num'])
                    ->group('statement_day')
                    ->select()->toArray();

                // 合并收入和支出数组
                $result = [];

                // 处理收入数据
                foreach ($income as $item) {
                    $result[$item['statement_day']]['income'] = $item['amount'];
                    $result[$item['statement_day']]['income_count'] = $item['num'];
                }

                // 处理支出数据
                foreach ($expenditure as $item) {
                    $result[$item['statement_day']]['expenditure'] = $item['amount'];
                    $result[$item['statement_day']]['expenditure_count'] = $item['num'];
                }
                // 确保每一天都有收入和支出字段（即使为0）
                foreach ($monthDays as $monthDay) {
                    $dayParts = explode("-", $monthDay);
                    $day = $dayParts[2]; // 获取日期部分

                    if (!isset($result[$day])) {
                        $result[$day] = [
                            'statement_date'=>$monthDay,
                            'income'=>0,
                            'income_count'=>0,
                            'expenditure' => 0,
                            'expenditure_count' => 0,
                            'accountType'=>$type_name,
                        ];
                    } else {
                        if (!isset($result[$day]['income'])) {
                            $result[$day]['income'] = 0;
                            $result[$day]['income_count'] = 0;
                            $result[$day]['account_type']=$type_name;
                        }
                        if (!isset($result[$day]['expenditure'])) {
                            $result[$day]['expenditure'] = 0;
                            $result[$day]['expenditure_count'] = 0;
                            $result[$day]['account_type']=$type_name;
                        }
                        $result[$day]['statement_date']=$monthDay;
                    }
                }
                krsort($result);
                $result=array_values($result);
                break;
            case Statement::MONTH:
                $monthByYear = Time::getMonthByYear($filter['statement_date']);
                $day = explode("-", $filter['statement_date']);
                $income= Statement::where('statement_year', $day[0])
                    ->where('amount', '>',0)
                    ->field(['statement_month','sum(amount) as amount','count(1) as num'])
                    ->group('statement_month')
                    ->select()->toArray();

                $expenditure= Statement::where('statement_year', $day[0])
                    ->where('amount', '<',0)
                    ->field(['statement_month','sum(amount) as amount','count(1) as num'])
                    ->group('statement_month')
                    ->select()->toArray();

                // 合并收入和支出数组
                $result = [];

                // 处理收入数据
                foreach ($income as $item) {
                    $result[$item['statement_month']]['income'] = $item['amount'];
                    $result[$item['statement_month']]['income_count'] = $item['num'];
                }

                // 处理支出数据
                foreach ($expenditure as $item) {
                    $result[$item['statement_month']]['expenditure'] = $item['amount'];
                    $result[$item['statement_month']]['expenditure_count'] = $item['num'];
                }

                // 确保每一天都有收入和支出字段（即使为0）
                foreach ($monthByYear as $monthDay) {
                    $dayParts = explode("-", $monthDay);
                    $day = $dayParts[1]; // 获取日期部分

                    if (!isset($result[$day])) {
                        $result[$day] = [
                            'statement_date'=>$monthDay,
                            'income'=>0,
                            'income_count'=>0,
                            'expenditure' => 0,
                            'expenditure_count' => 0,
                            'account_type' =>$type_name,
                        ];
                    } else {
                        if (!isset($result[$day]['income'])) {
                            $result[$day]['income'] = 0;
                            $result[$day]['income_count'] = 0;
                            $result[$day]['account_type']=$type_name;
                        }
                        if (!isset($result[$day]['expenditure'])) {
                            $result[$day]['expenditure'] = 0;
                            $result[$day]['expenditure_count'] = 0;
                            $result[$day]['account_type']=$type_name;
                        }

                        $result[$day]['statement_date']=$monthDay;
                    }
                }
                // 重新索引数组，以日期为键
                krsort($result);
                $result=array_values($result);
                break;
            case Statement::YEAR:
                $income= Statement::where('statement_year', $filter['statement_date'])
                    ->where('amount', '>',0)
                    ->field(['statement_year','sum(amount) as amount','count(1) as num'])
                    ->find();

                $expenditure= Statement::where('statement_year', $filter['statement_date'])
                    ->where('amount', '<',0)
                    ->field(['statement_year','sum(amount) as amount','count(1) as num'])
                    ->find();
                $result = [
                    [
                        'income'=>$income['amount']??0,
                        'income_count'=>$income['num']??0,
                        'expenditure' => $expenditure['amount']??0,
                        'expenditure_count' => $expenditure['num']??0,
                        'account_type' =>$type_name,
                    ]
                ];
                break;
        }

        return $result;
    }

    public function saveStatementDownload(array $filter)
    {

        return StatementDownload::create($filter);
    }


    public function getStatementQueryConfig()
    {


        $statement = Statement::STATEMENT_TYPE_NAME;
        $statementType = []; // 初始化为空数组

        foreach ($statement as $key => $item) {
            $statementType[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        $statementTimeList = Statement::STATEMENT_TIME_TYPE;
        $statementTime = []; // 初始化为空数组

        foreach ($statementTimeList as $key => $item) {
            $statementTime[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        $accountTypeList = Statement::ACCOUNT_TYPE_NAME;
        $accountType = []; // 初始化为空数组

        foreach ($accountTypeList as $key => $item) {
            $accountType[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        $entryTypeList = Statement::ENTRY_TYPE_NAME;
        $entryType = []; // 初始化为空数组

        foreach ($entryTypeList as $key => $item) {
            $entryType[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        $payMethodTypeList = Statement::PAY_TYPE_NAME;
        $payMethodType = []; // 初始化为空数组

        foreach ($payMethodTypeList as $key => $item) {
            $payMethodType[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        $dateComponentTypeList = Statement::DATE_COMPONENT_TYPE;
        $dateComponentType = []; // 初始化为空数组

        foreach ($dateComponentTypeList as $key => $item) {
            $dateComponentType[] = [  // 将元素添加到数组中
                'code' => $key,
                'description' => $item,
            ];
        }

        return [
            'statement_type' => $statementType,
            'statement_time_type' => $statementTime,
            'account_type' => $accountType,
            'entry_type' => $entryType,
            'pay_method_type' => $payMethodType,
            'date_component_type' => $dateComponentType,
        ];

    }


    /**
     * 保存对账单
     *
     * @param array $param 参数数组
     * @return void
     * @throws \Exception
     */
    public function saveStatement($param)
    {
        // 验证关联的单据是否可用
        $recordId = isset($param['recordId']) ? $param['recordId'] : null;
        if ($recordId === null) {
            throw new ApiException(lang('关联单据ID不能为空'));
        }
        if ($recordId <= 0) {
            throw new ApiException(lang('关联单据ID不能为空'));
        }

        // 验证对账单类型
        $statementType = isset($param['type']) ? $param['type'] : null;
        if ($statementType === null) {
            throw new ApiException(lang('类型参数不能为空'));
        }

        $dt = new \DateTime();

        $data = [
            'account_type' => $param['accountType'],
            'type' => $statementType,
            'entry_type' => $param['entryType'],
            'payment_type' => $param['paymentType'],
            'gmt_create' => date('Y-m-d H:i:s'),
            'vendor_id' => $param['vendorId'],
            'shop_id' => $param['shopId'],
            'statement_year' => $dt->format('Y'),
            'statement_month' => $dt->format('n'),
            'statement_day' => $dt->format('j'),
            'settlement_time' => time(),
        ];

        // 获取供应商和店铺信息
        $vendor = null;
        $shop = null;

        if (!empty($param['vendorId'])) {
            $vendor = Vendor::find($param['vendorId']);
        }

        if (!empty($param['shopId'])) {
            $shop = Shop::find($param['shopId']);
        }

        // 通过对账单类型获取单据的账单信息
        switch ($statementType) {
            case 3:
                // 获取订单的账单信息
                $this->getOrderStatementInfo($recordId, $data);
                if ($shop !== null) {
                    // 生成订单的shop服务费信息
                    $this->saveServiceFee($data, $shop['service_fee_rate'], 'storeGeneralServiceFeeRate', 2);
                }
                if ($vendor !== null) {
                    // 生成订单的vendor服务费信息
                    $this->saveServiceFee($data, $vendor['service_fee_rate'], 'supplierGeneralServiceFeeRate', 2);
                }
                break;

            case 4:
                // 获取店铺的提现账单信息
                $this->getShopStatementInfo($shop, $recordId, $data);
                if ($shop !== null) {
                    // 生成店铺的手续费信息
                    $this->saveServiceFee($data, $shop['fee_rate'], 'storefrontWithdrawalFeeRate', 1);
                }
                break;

            case 5:
                // 获取门店的提现账单信息
                $this->getShopStatementInfo($shop, $recordId, $data);
                if ($shop !== null) {
                    // 生成门店的的提现手续费信息
                    $this->saveServiceFee($data, $shop['fee_rate'], 'storefrontWithdrawalFeeRate', 5);
                }
                break;

            case 6:
                // 获取供应商的账单信息
                $this->getSupplierStatementInfo($vendor, $recordId, $data);
                if ($vendor !== null) {
                    // 生成供应商的手续费信息
                    $this->saveServiceFee($data, $vendor['fee_rate'], 'supplierWithdrawalFeeRate', 6);
                }
                break;

            // 实际上这个类型不会单独处理
            case 1:
            case 2:
                // 不执行任何操作
                break;
        }

        // 保存对账单
        $statement = new Statement();
        $statement->save($data);
    }

    /**
     * 保存服务费/手续费
     *
     * @param array $statementData 账单数据
     * @param float|null $serviceFeeRate 服务费率/手续费率
     * @param string $settingsEnum 配置枚举
     * @param int $feeType 费用类型
     * @return void
     */
    private function saveServiceFee($statementData, $serviceFeeRate, $settingsEnum, $feeType)
    {
        $shopServiceFeeRate = $serviceFeeRate;
        if ($shopServiceFeeRate === null || $shopServiceFeeRate <= 0) {
            $configVal = Config::get($settingsEnum);
            $shopServiceFeeRate = floatval($configVal);
        }

        $serviceFeeData = $statementData;
        if ($feeType === 1) {
            $serviceFeeData['type'] = 1;
        } elseif ($feeType === 2) {
            $serviceFeeData['type'] = 2;
        }

        $amount = $statementData['amount'];
        $calculatedAmount = bcmul(strval($amount), strval($shopServiceFeeRate), 10);
        $dividedAmount = bcdiv($calculatedAmount, '100', 2);
        $serviceFeeData['amount'] = $dividedAmount;

        $serviceFeeStatement = new Statement();
        $serviceFeeStatement->save($serviceFeeData);
    }

    /**
     * 获取订单的账单信息
     *
     * @param int $recordId 关联的订单ID
     * @param array $data Statement数据数组（引用传递）
     * @return void
     */
    private function getOrderStatementInfo($recordId, &$data)
    {
        $order = Order::find($recordId);
        if ($order === null) {
            throw new ApiException(lang('关联的订单不存在'));
        }

        $data['record_id'] = $recordId;
        $data['record_sn'] = $order['order_sn'];
        $data['amount'] = $order['paid_amount'];
        $data['record_time'] = $order['add_time'];
    }

    /**
     * 获取店铺的账单信息
     *
     * @param mixed $shop 店铺信息
     * @param int $recordId 提现申请ID
     * @param array $data Statement数据数组（引用传递）
     * @return void
     */
    private function getShopStatementInfo($shop, $recordId, &$data)
    {
        if ($shop === null) {
            throw new ApiException(lang('关联的店铺或门店不存在'));
        }

        $shopWithdraw = ShopWithdraw::find($recordId);
        if ($shopWithdraw === null) {
            throw new ApiException(lang('关联的提现申请不存在'));
        }

        $data['record_id'] = $recordId;
        $data['record_sn'] = $shopWithdraw['withdraw_sn'];
        $data['amount'] = $shopWithdraw['amount'];
        $data['record_time'] = $shopWithdraw['add_time'];
        $data['account_balance'] = $shop['shop_money'];
    }

    /**
     * 获取供应商的账单信息
     *
     * @param mixed $vendor 供应商信息
     * @param int $recordId 提现申请ID
     * @param array $data Statement数据数组（引用传递）
     * @return void
     */
    private function getSupplierStatementInfo($vendor, $recordId, &$data)
    {
        $vendorWithdraw = VendorWithdraw::find($recordId);
        if ($vendorWithdraw === null) {
            throw new ApiException(lang('关联的提现申请不存在'));
        }

        if ($vendor === null) {
            throw new ApiException(lang('关联的供应商不存在'));
        }

        $data['record_id'] = $recordId;
        $data['record_sn'] = $vendorWithdraw['withdraw_sn'];
        $data['amount'] = $vendorWithdraw['amount'];
        $data['record_time'] = $vendorWithdraw['add_time'];
        $data['account_balance'] = $vendor['vendor_money'];
    }

}