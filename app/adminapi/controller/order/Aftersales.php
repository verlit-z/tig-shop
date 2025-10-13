<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 退换货
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\order;

use app\adminapi\AdminBaseController;
use app\service\admin\order\AftersalesService;
use exceptions\ApiException;
use think\App;
use think\Response;

/**
 * 退换货控制器
 */
class Aftersales extends AdminBaseController
{
    protected AftersalesService $aftersalesService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AftersalesService $aftersalesService
     */
    public function __construct(App $app, AftersalesService $aftersalesService)
    {
        parent::__construct($app);
        $this->aftersalesService = $aftersalesService;
        $this->checkAuthor('aftersalesManage'); //权限检查
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
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'aftersale_id',
            'sort_order' => 'desc',
            'status/d' => 0,
            'aftersale_type/d' => 0,
        ], 'get');

        $filter['shop_id'] = $this->shopId;
        $filter['vendor_id'] = request()->vendorId;

        $filterResult = $this->aftersalesService->getFilterResult($filter);
        $total = $this->aftersalesService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
            'status_list' => \app\model\order\Aftersales::STATUS_NAME,
        ]);
    }

    /**
     * 售后类型接口
     * @return Response
     */
    public function applyType()
    {
        return $this->success(
            \app\model\order\Aftersales::AFTERSALES_TYPE_NAME,
        );
    }

    /**
     * 售后状态接口
     * @return Response
     */
    public function returnGoodsStatus()
    {


        $result = \app\model\order\Aftersales::STATUS_NAME;
        if(config('app.IS_VENDOR')!=1){
            unset($result[21],$result[22]);
        }
        return $this->success($result);
    }

    /**
     * 详情接口
     *
     * @return Response
     */
    public function detail():Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->aftersalesService->getDetail($id);
        $item['status_config'] = \app\model\order\Aftersales::STATUS_NAME;
        $item['aftersales_type_config'] = \app\model\order\Aftersales::AFTERSALES_TYPE_NAME;
        $item['refuse_reason'] = \app\model\order\Aftersales::REFUSE_REASON;
        $refundProductPrice = 0;
        foreach ($item['aftersales_items'] as $v) {
            $refundProductPrice += $v['number'] * $v['price'];
        }
        $item['suggest_refund_amount'] = (float)bcadd($refundProductPrice, 0,2);
        return $this->success(
           $item
        );
    }

    /**
     *
     * 同意或拒接售后接口
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'aftersale_id' => $id,
            'status/d' => 0,
            'reply' => '',
            'return_address' => '',
            'refund_amount' => 0,
        ], 'post');

        $result = $this->aftersalesService->agreeOrRefuse($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('操作失败');
        }
    }

    /**
     * 售后完结
     * @return Response
     * @throws ApiException
     */
    public function complete(): Response
    {
        $id =$this->request->all('id/d', 0);
        $admin_id = request()->adminUid;
        $result = $this->aftersalesService->complete($id,$admin_id);
        return $result ? $this->success() : $this->error('操作失败');
    }

    /**
     *
     * 售后确认收货接口
     * @return Response
     */
    public function receive(): Response
    {
        $id =$this->request->all('id/d', 0);
        $result = $this->aftersalesService->receive($id);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('更新失败');
        }
    }

    /**
     * 提交售后反馈记录
     * @return Response
     * @throws ApiException
     */
    public function record(): Response
    {
        $input = $this->request->only([
            'aftersale_id/d' => 0,
            'log_info' => '',
            'return_pic' => [],
        ]);
        $id = $input['aftersale_id'];
        unset($input['aftersale_id']);
        $result = $this->aftersalesService->submitFeedbackRecord($id, $input, request()->adminUid);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('更新失败');
        }
    }
}
