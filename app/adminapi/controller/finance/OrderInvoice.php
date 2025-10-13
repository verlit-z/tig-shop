<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 发票申请
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\finance;

use app\adminapi\AdminBaseController;
use app\service\admin\finance\OrderInvoiceService;
use app\validate\finance\OrderInvoiceValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 发票申请控制器
 */
class OrderInvoice extends AdminBaseController
{
    protected OrderInvoiceService $orderInvoiceService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param OrderInvoiceService $orderInvoiceService
     */
    public function __construct(App $app, OrderInvoiceService $orderInvoiceService)
    {
        parent::__construct($app);
        $this->orderInvoiceService = $orderInvoiceService;
        $this->checkAuthor('orderInvoiceManage'); //权限检查
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
            'invoice_type/d' => 0,
            'status/d' => -1,
            'shop_type/d' => 0,
            'shop_id/d' => -1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->orderInvoiceService->getFilterResult($filter);
        $total = $this->orderInvoiceService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     *
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->orderInvoiceService->getDetail($id);
        return $this->success(
             $item
        );
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'status/d' => 0,
            'amount' => '0.00',
            'apply_reply' => '',
            'invoice_attachment' => []
        ], 'post');

        try {
            validate(OrderInvoiceValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->orderInvoiceService->updateOrderInvoice($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'发票申请更新失败');
        }
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->orderInvoiceService->deleteOrderInvoice($id);
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
                    $this->orderInvoiceService->deleteOrderInvoice($id);
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
