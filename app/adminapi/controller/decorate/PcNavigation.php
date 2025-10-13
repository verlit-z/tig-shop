<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- PC导航栏
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\PcNavigationService;
use app\validate\decorate\PcNavigationValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * PC导航栏控制器
 */
class PcNavigation extends AdminBaseController
{
    protected PcNavigationService $pcNavigationService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param PcNavigationService $pcNavigationService
     */
    public function __construct(App $app, PcNavigationService $pcNavigationService)
    {
        parent::__construct($app);
        $this->pcNavigationService = $pcNavigationService;
        $this->checkAuthor('pcNavigationManage'); //权限检查
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
            'is_show' => -1,
            'type/d' => 0,
            'parent_id/d' => 0,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->pcNavigationService->getFilterResult($filter);
        $total = $this->pcNavigationService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
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
        $item = $this->pcNavigationService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 上级导航处理
     * @return Response
     */
    public function getParentNav(): Response
    {
        //上级导航
        $type =$this->request->all('type/d', 0);
        $nav_list = $this->pcNavigationService->getParentNav($type);
        return $this->success(
            $nav_list
        );
    }

    /**
     * 选择链接地址
     * @return Response
     */
    public function selectLink(): Response
    {
        $base_link = $this->pcNavigationService->getBaseLink();
        return $this->success(
          $base_link
        );
    }

    /**
     * 请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'title' => '',
            'is_show/d' => 1,
            'is_blank/d' => 0,
            "link/a" => [],
            'type/d' => 0,
            'parent_id/d' => 0,
            'icon' => '',
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
            validate(PcNavigationValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->pcNavigationService->createNavigation($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'PC导航栏添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["id"] = $id;
        try {
            validate(PcNavigationValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->pcNavigationService->updateNavigation($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'PC导航栏更新失败');
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

        if (!in_array($field, ['sort_order', 'is_show', 'is_blank'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->pcNavigationService->updateNavigationField($id, $data);

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
        $this->pcNavigationService->deleteNavigation($id);
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
                    $this->pcNavigationService->deleteNavigation($id);
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
