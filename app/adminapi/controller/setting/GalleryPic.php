<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 相册图片
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\image\Image;
use app\service\admin\setting\GalleryPicService;
use app\service\admin\setting\GalleryService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;
use utils\Time;

/**
 * 相册图片控制器
 */
class GalleryPic extends AdminBaseController
{
    protected GalleryPicService $galleryPicService;
    protected GalleryService $galleryService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param GalleryPicService $galleryPicService
     */
    public function __construct(App $app, GalleryPicService $galleryPicService, GalleryService $galleryService)
    {
        parent::__construct($app);
        $this->galleryPicService = $galleryPicService;
        $this->galleryService = $galleryService;
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
            'gallery_id/d' => 0,
            'sort_field' => 'pic_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->galleryPicService->getFilterResult($filter);
        $total = $this->galleryPicService->getFilterCount($filter);
        if ($filter['gallery_id'] > 0) {
            $child_gallery_list = $this->galleryService->getFilterResult([
                'gallery_id' => $filter['gallery_id'],
                'page' => 1,
                'size' => 99,
                'sort_field' => 'gallery_id',
                'sort_order' => 'asc',
            ]);
            $gallery_info = $this->galleryService->getDetail($filter['gallery_id']);
        }
        return $this->success([
            'gallery_pic_page' => [
                'records' => $filterResult,
                'total' => $total,
            ],
            'child_gallery_list' => $child_gallery_list ?? [],
            'gallery_info' => $gallery_info ?? [],

        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->galleryPicService->getDetail($id);
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

        if (!in_array($field, ['pic_name', 'sort_order'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'pic_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->galleryPicService->updateGalleryPicField($id, $data);

        return $this->success();
    }

    /**
     * 图片上传
     * @return Response
     * @throws \exceptions\ApiException
     * @throws \think\Exception
     */
    public function uploadImg(): Response
    {
        $gallery_id =$this->request->all('gallery_id/d', 0);
        if ($gallery_id > 0) {
            $gallery_info = $this->galleryService->getDetail($gallery_id);
            if (!$gallery_info) {
                return $this->error(/** LANG */'不存在此相册');
            }
        }
        if (request()->file('file')) {
            $image = new Image(request()->file('file'), 'gallery');
            $original_img = $image->save();
            $thumb_img = $image->makeThumb(200, 200);
        } else {
            return $this->error(/** LANG */'图片上传错误！');
        }
        if (!$original_img || !$thumb_img) {
            return $this->error(/** LANG */'图片上传错误！');
        }
        $data = [
            'gallery_id' => $gallery_id,
            'pic_ower_id' => Request()->adminUid,
            'pic_url' => $original_img,
            'pic_thumb' => $thumb_img,
            'pic_name' => $image->orgName,
            'add_time' => Time::now(),
            'shop_id' => Request()->shopId,
            'vendor_id' => Request()->vendorId ?? 0,
        ];

        $id = $this->galleryPicService->createGalleryPic($data);

        return $this->success([
            'pic_thumb' => $data['pic_thumb'],
            'pic_url' => $data['pic_url'],
            'pic_name' => $data['pic_name'],
            'pic_id' => $id,
        ]);
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->galleryPicService->deleteGalleryPic($id);
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
                    $this->galleryPicService->deleteGalleryPic($id);
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
