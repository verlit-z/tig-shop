<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 首页分类栏
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\MobileCatNavService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 首页分类栏控制器
 */
class MobileCatNav extends AdminBaseController
{
    protected MobileCatNavService $mobileCatNavService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param MobileCatNavService $mobileCatNavService
     */
    public function __construct(App $app, MobileCatNavService $mobileCatNavService)
    {
        parent::__construct($app);
        $this->mobileCatNavService = $mobileCatNavService;
        $this->checkAuthor('mobileCatNavManage'); //权限检查
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
            'is_show/d' => -1,
            'paging/d' => 1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'mobile_cat_nav_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->mobileCatNavService->getFilterResult($filter);
        $total = $this->mobileCatNavService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 添加或编辑页面
     *
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->mobileCatNavService->getDetail($id);
        return $this->success(
             $item
        );
    }

    /**
     * 获取请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'category_id/d' => 0,
            "cat_color" => "",
            "img_url" => [],
            "child_cat_ids" => [],
            "brand_ids" => [],
            "is_show/d" => 1,
            'sort_order/d' => 50,
        ], 'post');
        return $data;
    }

    /**
     * 执行添加操作
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->requestData();

        $result = $this->mobileCatNavService->createMobileCatNav($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'首页分类栏更新失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["mobile_cat_nav_id"] = $id;
        $result = $this->mobileCatNavService->updateMobileCatNav($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'首页分类栏更新失败');
        }
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

        if (!in_array($field, ['sort_order', 'is_show'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'mobile_cat_nav_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->mobileCatNavService->updateMobileCatNavField($id, $data);

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
        $this->mobileCatNavService->deleteMobileCatNav($id);
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
                    $this->mobileCatNavService->deleteMobileCatNav($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success(/** LANG */'批量操作执行成功！');
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
