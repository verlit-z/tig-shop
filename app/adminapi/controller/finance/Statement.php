<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 对账单
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\finance;

use app\adminapi\AdminBaseController;
use app\service\admin\finance\StatementService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 对账单
 */
class Statement extends AdminBaseController
{




    protected StatementService $statementService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param StatementService $statementService
     */
    public function __construct(App $app, StatementService $statementService)
    {
        parent::__construct($app);
        $this->statementService = $statementService;
    }

    /**
     * 获取对账单列表
     *
     * @return Response
     */
    public function getStatementList()
    {

        $filter = $this->request->only( [
            'start_date_time'=>'',
            'end_date_time'=>'',
            'account_type/d'=>'0',
            'type/d'=>'0',
            'payment_type'=>'',
            'record_sn'=>'',
            'time_type/d'=>0,
            'shop_id/d'=>0,
            'vendor_id/d'=>0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'statement_id',
            'sort_order' => 'desc',
            'keyword' => '',
        ]);

        // 查询的时间类型，1：入账（结算）时间2：下单时间
        if ($filter['time_type'] == 1) {
            $filter['start_settlement_time'] = strtotime($filter['start_date_time']) ?: 0;
            $filter['end_settlement_time'] = strtotime($filter['end_date_time']) ?: 0;
            unset($filter['start_date_time'], $filter['end_date_time']);
        }elseif ($filter['time_type'] == 2) {
            $filter['start_record_time'] = strtotime($filter['start_date_time']) ?: 0;
            $filter['end_record_time'] = strtotime($filter['end_date_time']) ?: 0;
            unset($filter['start_date_time'], $filter['end_date_time']);
        }
        $list = $this->statementService->getFilterResult($filter);

        if (empty($list)) {
            return $this->success([
                'records' => [],
                'total' => 0,
            ]);
        }

        foreach ($list as $key => &$item) {
            $item['account_type_name'] = \app\model\finance\Statement::getAccountTypeName($item['account_type']);
            $item['entry_type_name'] = \app\model\finance\Statement::getEntryTypeName($item['entry_type']);
            $item['type_name'] = \app\model\finance\Statement::getStatementTypeName($item['type']);
            $item['payment_type_name'] = \app\model\finance\Statement::getPayTypeName($item['payment_type']);
        }

        $total = $this->statementService->getFilterCount($filter);
        return $this->success([
            'records' => $list,
            'total' => $total,
        ]);

    }


    public function saveStatementDownload()
    {

        $filter = $this->request->only( [
            'start_time'=>'',
            'end_time'=>'',
            'shop_id/d'=>0,
            'vendor_id/d'=>0,
            'remark'=>'',
        ]);

        $filter['gmt_create'] =time();
        $result = $this->statementService->saveStatementDownload($filter);
        return $result ? $this->success() : $this->error('导出失败');
    }

    /**
     * 导出对账单
     * @return Response
     */
    public function exportStatement()
    {
        $filter = $this->request->only( [
            'start_date_time'=>'',
            'end_date_time'=>'',
            'account_type/d'=>'0',
            'type/d'=>'0',
            'payment_type'=>'',
            'record_sn'=>'',
            'time_type/d'=>0,
            'shop_id/d'=>0,
            'vendor_id/d'=>0,
        ]);

        $result = $this->statementService->exportStatement($filter);
        return $result ? $this->success() : $this->error('导出失败');
    }

    public function exportStatementStatistics()
    {
        $filter = $this->request->only( [
            'statement_date'=>'',
            'statement_date_type'=>'',
            'account_type/d'=>'0',
        ]);

        if (empty($filter['statement_date_type'])) {
            return $this->error('请选择日期类型');
        }

        if (empty($filter['statement_date'])) {
            return $this->error('请选择日期');
        }

        $result = $this->statementService->exportStatementStatistics($filter);
        return $result ? $this->success() : $this->error('导出失败');
    }

    /**
     * 获取对账单统计列表
     * @return Response
     */
    public function getStatementStatisticsList()
    {

         $filter = $this->request->only( [
             'statement_date'=>'',
             'statement_date_type'=>'',
             'account_type/d'=>'0',
         ]);


        if (empty($filter['statement_date_type'])) {
            return $this->error('请选择日期类型');
        }

         if (empty($filter['statement_date'])) {
             return $this->error('请选择日期');
         }

         $list = $this->statementService->getStatementStatisticsList($filter);

         return $this->success($list);

    }


    /**
     * 查询字段
     * @return Response
     */
    public function getStatementQueryConfig()
    {
        $list = $this->statementService->getStatementQueryConfig();
        return $this->success($list);
    }

}