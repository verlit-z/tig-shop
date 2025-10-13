<?php

namespace app\service\admin\setting;

use log\AdminLog;
use app\model\setting\GalleryVideo;
use app\model\setting\GalleryVideoInfo;
use app\service\common\BaseService;
use exceptions\ApiException;

class GalleryVideoService extends BaseService
{
    protected GalleryVideo $galleryVideoModel;
    protected GalleryVideoInfo $galleryVideoInfoModel;

    public function __construct(GalleryVideo $galleryVideoModel, GalleryVideoInfo $galleryVideoInfoModel){
        $this->galleryVideoModel = $galleryVideoModel;
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
        $result = $query->page($filter['page'], $filter['size'])->select()->toArray();
        foreach ($result as $key => $value) {
            $result[$key]['gallery_video_info_list'] = app(GalleryVideoInfoService::class)->getFilterResult([
                    'page' => 1,
                    'size' => 4,
                    'id' => $value['id'],
                    'sort_field' => 'id',
                    'sort_order' => 'desc',
                ]
            );
        }
        return $result;
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
        $query = $this->galleryVideoModel->query();
        // 处理筛选条件
        $query->where('shop_id', request()->shopId);

        $query->where('vendor_id', request()->vendorId);

        $query->where('parent_id', $filter['id'] > 0 ? $filter['id'] : 0);

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     * @param int $id
     * @return GalleryVideo
     * @throws ApiException
     */
    public function getDetail(int $id): GalleryVideo
    {
        $result = $this->galleryVideoModel->where('id', $id)->find();
        if (!$result) {
            throw new ApiException(/** LANG */'相册不存在');
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
        return $this->galleryVideoModel::where('id', $id)->value('name');
    }

    /**
     * 创建相册
     * @param array $data
     * @return int
     */
    public function createGallery(array $data): int
    {
        $result = $this->galleryVideoModel->save($data);
        AdminLog::add('新增视频库:' . $data['name']);
        return $this->galleryVideoModel->getKey();
    }

    /**
     * 执行相册更新
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateGallery(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = $this->galleryVideoModel->where('id', $id)->save($data);
        AdminLog::add('更新视频库:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 更新单个字段
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateGalleryField(int $id, array $data): bool
    {
        $gallery = $this->galleryVideoModel->find($id);
        if (empty($gallery)) {
            throw new ApiException(/** LANG */'#id错误或相册不存在');
        }
        $result = $gallery->save($data);
        return $result !== false;
    }

    /**
     * 删除相册
     *
     * @param int $id
     * @return bool
     */
    public function deleteGallery(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $get_name = $this->getName($id);
        $result = $this->galleryVideoModel::destroy($id);

        if ($result) {
            AdminLog::add('删除视频库:' . $get_name);
        }

        return $result !== false;
    }

    public function getAllCategory(array $filter)
    {
        $list = $this->galleryVideoModel
            ->where($filter)
            ->select()
            ->toArray();
        $res = [];
        if(!empty($list)) {
            $res =  $this->buildTree($list);
        }
        return $res;
    }

    /**
     * 构建树形结构
     *
     * @param array $data
     * @return array
     */
    private function buildTree(array $data): array
    {
        $items = array();
        foreach ($data as $item) {
            $items[$item['id']] = $item;
            // 重命名字段以匹配要求的格式
            $items[$item['id']]['id'] = $item['id'];
            $items[$item['id']]['name'] = $item['name'];
            $items[$item['id']]['parentId'] = $item['parent_id'];
            $items[$item['id']]['categoryPic'] = ''; // 如果有图片字段可以在这里设置
            $items[$item['id']]['children'] = array();

            unset($items[$item['id']]['parent_id']);
        }

        $tree = array();
        foreach ($items as $item) {
            if ($item['parentId'] == 0) {
                $tree[] = &$items[$item['id']];
            } else {
                if (isset($items[$item['parentId']])) {
                    $items[$item['parentId']]['children'][] = &$items[$item['id']];
                }
            }
        }

        return $tree;
    }

}