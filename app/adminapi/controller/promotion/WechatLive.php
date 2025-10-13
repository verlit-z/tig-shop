<?php

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\WechatLiveService;
use exceptions\ApiException;
use think\App;
use think\Response;

class WechatLive extends AdminBaseController
{
	protected WechatLiveService $wechatLiveService;
	/**
	 * 构造函数
	 *
	 * @param App $app
	 * @param WechatLiveService $wechatLiveService
	 */
	public function __construct(App $app, WechatLiveService $wechatLiveService)
	{
		parent::__construct($app);
		$this->wechatLiveService = $wechatLiveService;
	}

	/**
	 * 列表
	 * @return Response
	 */
	public function list(): Response
	{
		$filter = $this->request->only([
			'wechat_live_title' => '',
			'live_status' => 0,
			'page/d' => 1,
			'size/d' => 15,
			'sort_field' => 'wechat_live_id',
			'sort_order' => 'desc',
		], 'get');
		$filter['shop_id'] = request()->shopId;

		$filterResult = $this->wechatLiveService->getFilterList($filter,[],['live_status_text','act_range_text']);
		$total = $this->wechatLiveService->getFilterCount($filter);

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
		$id =$this->request->all('id/d', 0);
		$item = $this->wechatLiveService->getDetail($id);
		if ($item->shop_id != request()->shopId) {
			throw new ApiException(/** LANG */'直播间不存在');
		}
		return $this->success(
			$item
		);
	}

	/**
	 * 编辑
	 * @return Response
	 * @throws ApiException
	 */
	public function update(): Response
	{
		$id =$this->request->all('id/d', 0);

		$item = $this->wechatLiveService->getDetail($id);
		if ($item->shop_id != request()->shopId) {
			throw new ApiException(/** LANG */'直播间不存在');
		}

		$data = $this->request->only([
			'wechat_live_id/d' => $id,
			'wechat_live_title' => '',
			'act_range/d' => 0,
			'act_range_ext/a' => []
		],"post");

		$result = $this->wechatLiveService->updateWechatLive($id,$data);
		return $result ? $this->success() : $this->error(/** LANG */'直播修改失败');
	}

	/**
	 * api 更新直播间
	 * @return Response
	 */
	public function refreshByApi(): Response
	{
		$shop_id = request()->shopId;
		$result = $this->wechatLiveService->refreshByApi($shop_id);
		return $result ? $this->success() : $this->error(/** LANG */'直播更新失败');
	}
}