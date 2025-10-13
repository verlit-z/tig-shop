<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 相册
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\GalleryService;
use app\validate\setting\GalleryValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 相册控制器
 */
class Gallery extends AdminBaseController
{
    protected GalleryService $galleryService;
    protected $sort = 2; //排序

    /**
     * 构造函数
     *
     * @param App $app
     * @param GalleryService $galleryService
     */
    public function __construct(App $app, GalleryService $galleryService)
    {
        parent::__construct($app);
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
            'gallery_id/d' => 0,
            'page/d' => 1,
            'size/d' => 99,
            'sort_field' => 'gallery_id',
            'sort_order' => 'asc',
        ], 'get');
        $filterResult = $this->galleryService->getFilterResult($filter);
        $total = $this->galleryService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'size' => $filter['size'],
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
        $item = $this->galleryService->getDetail($id);
        return $this->success(
           $item
        );
    }

    /**
     * 添加
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->request->only([
            'gallery_name' => '',
            'parent_id' => '',
            'gallery_sort' => 50,
        ], 'post');
        $data['shop_id'] = request()->shopId;
        $data['vendor_id'] = request()->vendorId ?? 0;
        try {
            validate(GalleryValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->galleryService->createGallery($data);
        if ($result) {
            return $this->success();
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
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'gallery_id' => $id,
            'gallery_name' => '',
            'parent_id' => '',
            'gallery_sort' => 50,
        ], 'post');

        try {
            validate(GalleryValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->galleryService->updateGallery($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'相册更新失败');
        }
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

		if (!in_array($field, ['gallery_name'])) {
			return $this->error('#field 错误');
		}

		$data = [
			'gallery_id' => $id,
			$field =>$this->request->all('val'),
		];

		$this->galleryService->updateGalleryField($id, $data);

		return $this->success();
	}

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->galleryService->deleteGallery($id);
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
                    $this->galleryService->deleteGallery($id);
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
