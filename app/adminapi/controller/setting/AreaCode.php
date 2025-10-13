<?php

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\AreaCodeService;
use app\validate\setting\AreaCodeValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

class AreaCode extends AdminBaseController
{
	protected AreaCodeService $areaCodeService;

	/**
	 * 构造函数
	 * @param App $app
	 * @param AreaCodeService $areaCodeService
	 */
	public function __construct(App $app, AreaCodeService $areaCodeService)
	{
		parent::__construct($app);
		$this->areaCodeService = $areaCodeService;
	}

	/**
	 * 列表
	 * @return Response
	 */
	public function list(): Response
	{
		$filter = $this->request->only([
			'name' => '',
			'page/d' => 1,
			'size/d' => 15,
			'sort_field' => 'id',
			'sort_order' => 'desc',
			'sort_other_field' => "is_default",
			'sort_other_order' => 'desc'
		], 'get');
		$filterResult = $this->areaCodeService->getFilterList($filter);
		$total = $this->areaCodeService->getFilterCount($filter);

		return $this->success([
			'records' => $filterResult,
			'total' => $total,
		]);
	}

	/**
	 * 详情
	 * @return Response
	 * @throws ApiException
	 */
	public function detail(): Response
	{
		$id =$this->request->all('id/d',0);
		$detail = $this->areaCodeService->getDetail($id);
		return $this->success($detail);
	}

	/**
	 * 创建
	 * @return Response
	 */
	public function create(): Response
	{
		$data = $this->request->only([
			'name' => '',
			'code' => '',
			'is_default/d' => 0,
			'is_available/d' => 0
		], 'post');
		try {
			validate(AreaCodeValidate::class)
				->scene('create')
				->check($data);
		} catch (ValidateException $e) {
			return $this->error($e->getError());
		}
		$result = $this->areaCodeService->createAreaCode($data);
		return $result ? $this->success() : $this->error(/** LANG */'添加失败');
	}

	/**
	 * 更新
	 * @return Response
	 * @throws ApiException
	 */
	public function update(): Response
	{
		$id =$this->request->all('id/d',0);
		$data = $this->request->only([
			'id' => $id,
			'name' => '',
			'code' => '',
			'is_default/d' => 0,
			'is_available/d' => 0
		], 'post');

		try {
			validate(AreaCodeValidate::class)
				->scene('update')
				->check($data);
		} catch (ValidateException $e) {
			return $this->error($e->getError());
		}
		$result = $this->areaCodeService->updateAreaCode($id,$data);
		return $result ? $this->success() : $this->error(/** LANG */'编辑失败');
	}

	/**
	 * 删除
	 * @return Response
	 * @throws ApiException
	 */
	public function del(): Response
	{
		$id =$this->request->all('id/d',0);
		$result = $this->areaCodeService->deleteAreaCode($id);
		return $result ? $this->success() : $this->error(/** LANG */'删除失败');
	}

	/**
	 * 更新字段
	 * @return Response
	 * @throws ApiException
	 */
	public function updateField(): Response
	{
		$id =$this->request->all('id/d', 0);
		$field =$this->request->all('field', '');
		if (!in_array($field, ['code','name', 'is_available','is_default'])) {
			return $this->error(/** LANG */'#field 错误');
		}
		$data = [
			'id' => $id,
			$field =>$this->request->all('val'),
		];
		$this->areaCodeService->updateAreaCodeField($id, $data);
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

		if (in_array($this->request->all('type'),['del'])) {
			try {
				//批量操作一定要事务
				Db::startTrans();
				foreach ($this->request->all('ids') as $id) {
					$id = intval($id);
					$this->areaCodeService->batchOperation($id);
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