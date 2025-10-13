<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 管理员消息
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\msg;

use app\adminapi\AdminBaseController;
use app\im\service\conversation\MessageService;
use app\service\admin\msg\AdminMsgService;
use app\service\admin\order\OrderService;
use think\App;
use think\Response;
use utils\Time;

/**
 * 示例模板控制器
 */
class AdminMsg extends AdminBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     * @param AdminMsgService $AdminMsgService
     */
    public function __construct(App $app, protected AdminMsgService $AdminMsgService)
    {
        parent::__construct($app);
        $this->checkAuthor('adminMsg'); //权限检查
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
            'msg_type' => 11,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => ['is_readed' => 'asc', 'msg_id' => 'desc'],
            'sort_order' => '',
            'shop_id/d' => -2, // 店铺id
			'suppliers_type/d' => 0
        ], 'get');
        $filter['shop_id'] = $this->shopId;
		$filter['suppliers_id'] = request()->suppliersId;
        $filter['vendor_id'] = request()->vendorId ?? 0;
        $filterResult = $this->AdminMsgService->getFilterResult($filter);
        $total = $this->AdminMsgService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 获得消息类型
     * @return Response
     */
    public function msgTypeArr(): Response
    {
        $vendor_id = request()->vendorId ?? 0;
        $msg_type_arr = $this->AdminMsgService->getMsgType($this->shopId, $vendor_id);
        return $this->success($msg_type_arr);
    }

	/**
	 * 配置项
	 * @return Response
	 */
	public function config(): Response
	{
		$order_type = \app\model\msg\AdminMsg::ORDER_RELATED_TYPE;
		$product_type = \app\model\msg\AdminMsg::PRODUCT_RELATED_TYPE;
		return $this->success([
			'order_type' => $order_type,
			'product_type' => $product_type,
		]);
	}

    /**
     * 设置单个已读
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function setReaded(): Response
    {
        $id =$this->request->all('msg_id/d', 0);
        $this->AdminMsgService->setReaded($id);
        return $this->success();
    }

    /**
     * 设置全部已读
     * @return Response
     */
    public function setAllReaded(): Response
    {
        $vendor_id = request()->vendorId ?? 0;
        $shop_id = $this->shopId;
        $this->AdminMsgService->setAllReaded($shop_id, $vendor_id);
        return $this->success();
    }

    /**
     * 获得消息数据
     * @return Response
     */
    public function getMsgCount(): Response
    {
        $startTime =$this->request->all('start_time', 0);
        $orderCount = app(OrderService::class)->getFilterCount([
			'shop_id' => $this->shopId,
            'add_start_time' => Time::format($startTime),
            'add_end_time' => Time::getCurrentDatetime("Y-m-d")
        ]);

        $imMsgCount = 0;
        $filter = ['is_read' => 0];
        $filter['shop_id'] = $this->shopId;
        $unreadMsgCount = $this->AdminMsgService->getFilterCount($filter);
        return $this->success([
            'order_count' => $orderCount,
            'im_msg_count' => $imMsgCount,
            'unread_msg_count' => $unreadMsgCount,
        ]);
    }

}
