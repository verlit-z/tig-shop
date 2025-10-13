<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 交易日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\finance;

use app\adminapi\AdminBaseController;
use app\service\admin\pay\PayLogService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 交易日志控制器
 */
class Paylog extends AdminBaseController
{
    protected PayLogService $paylogService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param PaylogService $paylogService
     */
    public function __construct(App $app, PayLogService $paylogService)
    {
        parent::__construct($app);
        $this->paylogService = $paylogService;
        $this->checkAuthor('paylogManage'); //权限检查
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'pay_status' => -1,
            'order_id/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'paylog_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->paylogService->getFilterResult($filter);
        $total = $this->paylogService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id = $this->request->all('id/d', 0);
        $this->paylogService->deletePaylog($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return Response
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->paylogService->deletePaylog($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
