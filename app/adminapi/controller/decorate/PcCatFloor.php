<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- PC分类抽屉
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\PcCatFloorService;
use app\validate\decorate\PcCatFloorValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\Response;

/**
 * PC分类抽屉控制器
 */
class PcCatFloor extends AdminBaseController
{
    protected PcCatFloorService $pcCatFloorService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param PcCatFloorService $pcCatFloorService
     */
    public function __construct(App $app, PcCatFloorService $pcCatFloorService)
    {
        parent::__construct($app);
        $this->pcCatFloorService = $pcCatFloorService;
        $this->checkAuthor('pcCatFloorManage'); //权限检查
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'is_show/d' => -1,
            'sort_field' => 'cat_floor_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->pcCatFloorService->getFilterResult($filter);
        $total = $this->pcCatFloorService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     *
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->pcCatFloorService->getDetail($id);
        return $this->success(
             $item
        );
    }

    /**
     * 请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'category_ids' => [],
            "category_names" => [],
            "floor_ico" => '',
            "hot_cat" => '',
            "is_show/d" => 1,
            "floor_ico_font" => '',
            "brand_ids" => [],
            'sort_order/d' => 50,
        ], 'post');

        return $data;
    }

    /**
     * 添加
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->requestData();

        try {
            validate(PcCatFloorValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->pcCatFloorService->createPcCatFloor($data);
        if ($result) {
            Cache::tag('cat')->clear();
            return $this->success();
        } else {
            return $this->error(/** LANG */'PC分类抽屉添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update()
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["cat_floor_id"] = $id;
        try {
            validate(PcCatFloorValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->pcCatFloorService->updatePcCatFloor($id, $data);
        if ($result) {
            Cache::tag('cat')->clear();
            return $this->success();
        } else {
            return $this->error(/** LANG */'PC分类抽屉更新失败');
        }
    }

    /**
     * 更新缓存
     * @return Response
     */
    public function clearCache()
    {
        Cache::tag('cat')->clear();
        return $this->success();
    }

    /**
     * 更新单个字段
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['sort_order', 'is_show'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'cat_floor_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->pcCatFloorService->updatePcCatFloorField($id, $data);
        Cache::tag('cat')->clear();
        return $this->success();
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->pcCatFloorService->deletePcCatFloor($id);
        Cache::tag('cat')->clear();
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
                    $this->pcCatFloorService->deletePcCatFloor($id);
                }
                Db::commit();
                Cache::tag('cat')->clear();
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
