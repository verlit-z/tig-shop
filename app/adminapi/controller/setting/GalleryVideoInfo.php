<?php

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\image\Image;
use app\service\admin\setting\GalleryVideoInfoService;
use app\service\admin\setting\GalleryVideoService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;
use utils\Config as UtilsConfig;
use utils\Time;
use think\File\UploadedFile;

class GalleryVideoInfo extends AdminBaseController
{
    protected GalleryVideoInfoService $galleryVideoInfoService;
    protected GalleryVideoService $galleryVideoService;

    /**
     * @param App $app
     * @param GalleryVideoInfoService $galleryVideoInfoService
     * @param GalleryVideoService $galleryVideoService
     */
    public function __construct(App $app, GalleryVideoInfoService $galleryVideoInfoService, GalleryVideoService $galleryVideoService)
    {
        parent::__construct($app);
        $this->galleryVideoInfoService = $galleryVideoInfoService;
        $this->galleryVideoService = $galleryVideoService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'id/d' => 0,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');
        $filterResult = $this->galleryVideoInfoService->getFilterResult($filter);
        $total = $this->galleryVideoInfoService->getFilterCount($filter);
        if ($filter['id'] > 0) {
            $child_gallery_list = $this->galleryVideoService->getFilterResult([
                'id' => $filter['id'],
                'page' => 1,
                'size' => 99,
                'sort_field' => 'id',
                'sort_order' => 'asc',
            ]);
            $gallery_info = $this->galleryVideoService->getDetail($filter['id']);
        }
        return $this->success([
            'gallery_video_info_page' => [
                'records' => $filterResult,
                'total' => $total,
            ],
            'child_gallery_list' => $child_gallery_list ?? [],
            'gallery_video' => $gallery_info ?? [],

        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->galleryVideoInfoService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['video_name', 'sort_order'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'pic_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->galleryVideoInfoService->updateGalleryPicField($id, $data);

        return $this->success();
    }

    /**
     * 视频上传
     * @return Response
     * @throws \exceptions\ApiException
     * @throws \think\Exception
     */

    public function uploadVideo(): Response
    {
        $gallery_id =$this->request->all('gallery_id/d', 0);
        if ($gallery_id > 0) {
            $gallery_info = $this->galleryVideoService->getDetail($gallery_id);
            if (!$gallery_info) {
                return $this->error(/** LANG */'不存在此视频库');
            }
        }

        if (request()->file('file')) {
            $file = request()->file('file');
            //获取文件大小
            $size = $file->getSize();
            //转换成MB
            $size = round($size / 1024 / 1024, 2);
            //获取上传大小配置
            $max_upload_size = UtilsConfig::get('uploadMaxSize') ?? 100;
            if($size > $max_upload_size) {
                return $this->error(/** LANG */'上传文件大小已超过后台配置，请从后台设置内修改！');
            }
            $format = $file->getOriginalExtension();
            $image = new Image($file, 'gallery_video', 'video');
            $original_img = $image->save();
        } else {
            return $this->error(/** LANG */'视频上传错误！');
        }

        if (!$original_img ) {
            return $this->error(/** LANG */'视频上传错误！');
        }

        return $this->success([
            'video_url' => $original_img,
            'video_name' =>  $image->orgName,
            'format' => $format,
            //'id' => $id,
        ]);
    }

    public function create()
    {
        $gallery_id_arr =$this->request->all('gallery_id/d', []);
        if(empty($gallery_id_arr)) {
            return $this->error(/** LANG */'未选择视频库');
        }

        $data = $this->request->only([
            'video_url' => '',
            'video_name' => '',
            'video_cover' => '',
            'format' => '',
            'video_first_frame' => '',
            'duration' => '',
            'size' => ''
        ], 'post');
        $data['shop_id'] = Request()->shopId;
        $data['gallery_id'] = end($gallery_id_arr);
        $data['vendor_id'] = Request()->vendorId;

        $this->galleryVideoInfoService->createGalleryPic($data);
        return $this->success();
    }

    public function update()
    {
        $galleryIds = $this->request->all('galleryId/d', []);
        if(empty($galleryIds)) {
            return $this->error(/** LANG */'未选择视频相册');
        }
        $galleryId = end($galleryIds);
        $data = $this->request->only([
            'id' => 1,
            'gallery_id' => $galleryId,
            'video_url' => '',
            'video_name' => '',
            'video_cover' => '',
            'format' => '',
            'video_first_frame' => '',
            'duration' => '',
            'size' => ''
        ], 'post');
        $data['shop_id'] = Request()->shopId;
        if(!$data['id']) return $this->error(/** LANG */'#id错误');
        $this->galleryVideoInfoService->updateGalleryPicField($data['id'], $data);
        return $this->success();
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->galleryVideoInfoService->deleteGalleryPic($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return Response
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
                    $this->galleryVideoInfoService->deleteGalleryPic($id);
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