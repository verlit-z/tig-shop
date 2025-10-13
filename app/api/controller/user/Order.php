<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 商品
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\order\OrderService;
use think\App;
use utils\Util;

/**
 * 会员中心订单控制器
 */
class Order extends IndexBaseController
{

    protected OrderService $orderService;

    /**
     * 构造函数
     * @param App $app
     * @param OrderService $orderService
     * @throws \exceptions\ApiException
     */
    public function __construct(App $app, OrderService $orderService)
    {
        parent::__construct($app);
        $this->orderService = $orderService;
    }

    /**
     * @return \think\Response
     * 会员中心订单列表
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'order_status/d' => -1,
            'pay_status/d' => -1,
            'shipping_status/d' => -1,
            'comment_status/d' => -1,
            'date_type/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'order_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['user_id'] = request()->userId;
        $filterResult = $this->orderService->getFilterResult($filter);
        $total = $this->orderService->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 会员中心订单详情
     * @return \think\Response
     */
    public function detail(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $item = $this->orderService->getDetail($id, request()->userId);
        return $this->success($item);
    }

    /**
     * 订单列表各类订单数量
     * @return \think\Response
     */
    public function orderNum(): \think\Response
    {
        $item = $this->orderService->getOrderQuantity(request()->userId);
        return $this->success($item);
    }

    /**
     * 取消订单
     * @return \think\Response
     */
    public function cancelOrder(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $this->orderService->cancelOrder($id, request()->userId);
        return $this->success();
    }

    /**
     * 删除订单
     * @return \think\Response
     */
    public function delOrder(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $this->orderService->delOrder($id, request()->userId);
        return $this->success();
    }

    /**
     * 确认收货
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function confirmReceipt(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $this->orderService->confirmReceipt($id, request()->userId);
        return $this->success();
    }

    /**
     * 发货物流信息
     * @return \think\Response
     */
    public function shippingInfo(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $list = $this->orderService->getOrderShipping($id);
        return $this->success($list);
    }

    /**
     * 再次购买
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function buyAgain(): \think\Response
    {
        $id = $this->request->all('id/d', 0);
        $return = $this->orderService->buyAgain($id, request()->userId);
        if ($return) {
            return $this->success();
        } else {
            return $this->error();
        }

    }
}
