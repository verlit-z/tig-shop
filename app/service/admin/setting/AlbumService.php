<?php

namespace app\service\admin\setting;

use app\model\setting\Album;
use app\service\common\BaseService;
use exceptions\ApiException;

class AlbumService extends BaseService
{
	/**
	 * 筛选查询
	 * @param array $filter
	 * @return object|\think\db\BaseQuery
	 */
	public function filterQuery(array $filter): object
	{
		$query = Album::query();

		if (isset($filter['pic_type']) && !empty($filter['pic_type'])) {
			$query->where('pic_type', $filter['pic_type']);
		}
		return $query;
	}

	/**
	 * 更新单个字段
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws ApiException
	 */
	public function updateAlbumField(int $id, array $data):bool
	{
		$album = Album::find($id);
		if (empty($album)) {
			throw new ApiException(/** LANG */'#id错误,相册图片不存在');
		}
		$result = $album->save($data);
		return $result !== false;
	}

	/**
	 * 删除相册图片
	 * @param int $id
	 * @return bool
	 * @throws ApiException
	 */
	public function delAlbum(int $id):bool
	{
		$album = Album::find($id);
		if (empty($album)) {
			throw new ApiException(/** LANG */'#id错误,相册图片不存在');
		}
		$result = $album->delete();
		return $result !== false;
	}
}