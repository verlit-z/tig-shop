<?php

namespace app\service\admin\setting;

use app\adminapi\controller\setting\GalleryVideo;
use app\model\setting\GalleryVideoInfo;
use log\AdminLog;
use app\service\common\BaseService;
use exceptions\ApiException;

class GalleryVideoInfoService extends BaseService
{
    protected GalleryVideoInfo $galleryVideoInfoModel;

    public function __construct(GalleryVideoInfo $galleryVideoInfoModel){
        $this->galleryVideoInfoModel = $galleryVideoInfoModel;
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->galleryVideoInfoModel->query();
        // 处理筛选条件
        $query->where('shop_id', request()->shopId);

        $query->where('vendor_id', request()->vendorId);

        if (isset($filter['id']) && $filter['id'] !== null && $filter['id'] > 0) {
            $query->where('gallery_id', $filter['id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return GalleryVideoInfo
     * @throws ApiException
     */
    public function getDetail(int $id): GalleryVideoInfo
    {
        $result = $this->galleryVideoInfoModel->where('id', $id)->find();

        if (!$result) {
            throw new ApiException(/** LANG */'视频不存在');
        }

        return $result;
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return $this->galleryVideoInfoModel::where('id', $id)->value('video_name');
    }

    /**
     * 创建相册图片
     * @param array $data
     * @return int
     */
    public function createGalleryPic(array $data): int
    {
        $result = $this->galleryVideoInfoModel->save($data);
        AdminLog::add('新增视频:' . $data['video_name']);
        return $this->galleryVideoInfoModel->getKey();
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateGalleryPicField(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = $this->galleryVideoInfoModel::where('id', $id)->save($data);
        AdminLog::add('更新视频相册:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 删除相册图片
     *
     * @param int $id
     * @return bool
     */
    public function deleteGalleryPic(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $get_name = $this->getName($id);
        $result = $this->galleryVideoInfoModel::destroy($id);

        if ($result) {
            AdminLog::add('删除视频相册:' . $get_name);
        }

        return $result !== false;
    }
}