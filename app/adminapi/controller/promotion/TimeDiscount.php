<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 优惠活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\TimeDiscountService;
use app\validate\promotion\TimeDiscountValidate;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\helper\Arr;
use think\Response;

/**
 * 限时折扣控制器
 */
class TimeDiscount extends AdminBaseController
{
    protected TimeDiscountService $timeDiscountService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param TimeDiscountService $timeDiscountService
     */
    public function __construct(App $app, TimeDiscountService $timeDiscountService)
    {
        parent::__construct($app);
        $this->timeDiscountService = $timeDiscountService;
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
            'discount_id' => 0,
            'active_state' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'discount_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['shop_id'] = request()->shopId;
        $filterResult = $this->timeDiscountService->getFilterList($filter);
        $total = $this->timeDiscountService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('discount_id/d', 0);
        $shopId = request()->shopId;
        $item = $this->timeDiscountService->detailTimeDiscount($id,$shopId);
        return $this->success(
            $item
        );
    }

    /**
     * 获取请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'discount_id' => 0,
            'promotion_name' => '',
            'start_time' => 0,
            'end_time' => 0,
            'item' => '',
        ], 'post');

        return $data;
    }


    /**
     * 添加优惠活动
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create(): Response
    {
        $data = $this->requestData();
        unset($data['discount_id']);
        param_validate($data,TimeDiscountValidate::class,'create');
        $data['shop_id'] = request()->shopId;
        $this->timeDiscountService->createTimeDiscount($data);
        return $this->success();
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $data = $this->requestData();
        param_validate($data,TimeDiscountValidate::class,'update');
        $data['shop_id'] = request()->shopId;
        $discountId = Arr::pull($data,'discount_id');
        $this->timeDiscountService->updateTimeDiscount($discountId,$data);
        return $this->success();
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $discountId =$this->request->all('id/d', 0);
        $this->timeDiscountService->delTimeDiscount($discountId);
        return $this->success();
    }

	/**
	 * 批量操作
	 * @return Response
	 * @throws ApiException
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
					$this->timeDiscountService->delTimeDiscount($id);
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
