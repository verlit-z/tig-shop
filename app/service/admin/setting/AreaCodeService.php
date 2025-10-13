<?php

namespace app\service\admin\setting;

use app\model\setting\AreaCode;
use app\service\common\BaseService;
use exceptions\ApiException;

class AreaCodeService extends BaseService
{
	/**
	 * 筛选查询
	 * @param array $filter
	 * @return object|\think\db\BaseQuery
	 */
	public function filterQuery(array $filter): object
	{
		$query = AreaCode::query();

		if (isset($filter['name']) && !empty($filter['name'])) {
			$query->whereLike('name', "%{$filter['name']}%");
		}

		if (isset($filter['is_available']) && $filter['is_available'] != -1) {
			$query->where('is_available', $filter['is_available']);
		}

		if (isset($filter['is_default']) && $filter['is_default'] != -1) {
			$query->where('is_default', $filter['is_default']);
		}

		return $query;
	}

	/**
	 * 获取详情
	 * @param int $id
	 * @return AreaCode
	 * @throws ApiException
	 */
	public function getDetail(int $id): AreaCode
	{
		$areaCode = AreaCode::find($id);
		if (empty($areaCode)) {
			throw new ApiException(/** LANG */'信息不存在');
		}
		return $areaCode;
	}

	/**
	 * 创建
	 * @param array $data
	 * @return int
	 */
	public function createAreaCode(array $data): int
	{
		$default_code = AreaCode::where('is_default',1)->find();
		if (!empty($default_code)) {
			if (isset($data['is_default']) && $data['is_default']) {
				if (AreaCode::where('is_default',1)->count()) {
					AreaCode::where('is_default',1)->save(['is_default' => 0]);
				}
			}
		}
		$areaCode = AreaCode::create($data);
		return $areaCode->id;
	}

	/**
	 * 更新
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws ApiException
	 */
	public function updateAreaCode(int $id, array $data):bool
	{
		$areaCode = $this->getDetail($id);
		// 获取默认货币
		$default_code = AreaCode::where('is_default',1)->find();
		if (!empty($default_code)) {
			if (isset($data['is_default']) && $data['is_default']) {
				if (AreaCode::where('is_default',1)->count()) {
					AreaCode::where('is_default',1)->save(['is_default' => 0]);
				}
			}
		}
		return $areaCode->save($data);
	}

	/**
	 * 删除
	 * @param int $id
	 * @return bool
	 * @throws ApiException
	 */
	public function deleteAreaCode(int $id): bool
	{
		$areaCode = $this->getDetail($id);
		if ($areaCode->is_default) {
			throw new ApiException(/** LANG */'默认区号不能直接删除');
		}
		return $areaCode->delete();
	}

	/**
	 * 更新字段
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws ApiException
	 */
	public function updateAreaCodeField(int $id, array $data): bool
	{
		$areaCode = $this->getDetail($id);
		// 获取默认货币
		$default_code = AreaCode::where('is_default',1)->find();
		if (in_array('is_default',array_keys($data))) {
			if ($data['is_default']) {
				if (!empty($default_code)) {
					if ($default_code->id != $id) {
						// 改变默认
						AreaCode::where('is_default',1)->save(['is_default' => 0]);
					}
				}
			}
		}
		return $areaCode->save($data);
	}

	/**
	 * 批量操作
	 * @param int $id
	 * @return bool
	 * @throws ApiException
	 */
	public function batchOperation(int $id):bool
	{
		if (!$id) {
			throw new ApiException(/** LANG */'#参数错误');
		}
		$areaCode = $this->getDetail($id);
		if ($areaCode->is_default) {
			throw new ApiException(/** LANG */'默认区号不能直接删除');
		}
		return $areaCode->delete();
	}
}