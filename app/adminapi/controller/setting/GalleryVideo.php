<?php

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\GalleryVideoService;
use app\validate\setting\GalleryVideoValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\Response;
use think\facade\Db;

class GalleryVideo extends AdminBaseController
{
    protected GalleryVideoService $galleryVideoService;
    protected $sort = 2;

    /**
     * 构造函数
     * @param App $app
     * @param GalleryVideoService $galleryVideoService
     */
    public function __construct(App $app, GalleryVideoService $galleryVideoService) {
        parent::__construct($app);
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
            'id/d' => 0,
            'page/d' => 1,
            'size/d' => 99,
            'sort_field' => 'id',
            'sort_order' => 'asc',
        ], 'get');
        $filterResult = $this->galleryVideoService->getFilterResult($filter);
        $total = $this->galleryVideoService->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'size' => $filter['size'],
            'total' => $total,
        ]);
    }

    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->galleryVideoService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->request->only([
            'name' => '',
            'parent_id' => '',
            'sort' => 50,
        ], 'post');
        $data['shop_id'] = request()->shopId;
        $data['vendor_id'] = Request()->vendorId;
        try {
            validate(GalleryVideoValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $result = $this->galleryVideoService->createGallery($data);
        if ($result) {
            return $this->success($result);
        } else {
            return $this->error(/** LANG */'相册添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $data = $this->request->only([
            'id/d' => 0,
            'name' => '',
            'parent_id' => '',
            'sort' => 50,
        ], 'post');
        $data['shop_id'] = request()->shopId;
        try {
            validate(GalleryVideoValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $result = $this->galleryVideoService->updateGallery($data['id'], $data);
        if ($result) {
            return $this->success($data['id']);
        } else {
            return $this->error(/** LANG */'相册更新失败');
        }
    }

    /**
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['name'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->galleryVideoService->updateGalleryField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->galleryVideoService->deleteGallery($id);
        return $this->success();
    }

    /**
     * 批量操作
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
                    $this->galleryVideoService->deleteGallery($id);
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

    public function getAllCategory()
    {


        if (request()->adminType == 'admin') {
            $filter['shop_id'] = 0;
            $filter['vendor_id'] = 0;
        }

        if (request()->adminType == 'shop') {
            $filter['shop_id'] = request()->shopId;
        }

        if (request()->adminType == 'vendor') {
            $filter['vendor_id'] = request()->shopId;
        }


        $res = $this->galleryVideoService->getAllCategory($filter);
        return $this->success($res);

    }

}