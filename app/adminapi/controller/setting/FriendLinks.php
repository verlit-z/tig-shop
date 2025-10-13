<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 友情链接
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\FriendLinksService;
use app\validate\setting\FriendLinksValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 友情链接控制器
 */
class FriendLinks extends AdminBaseController
{
    protected FriendLinksService $friendLinksService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param FriendLinksService $friendLinksService
     */
    public function __construct(App $app, FriendLinksService $friendLinksService)
    {
        parent::__construct($app);
        $this->friendLinksService = $friendLinksService;
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
            'sort_field' => 'link_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->friendLinksService->getFilterResult($filter);
        $total = $this->friendLinksService->getFilterCount($filter);

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
        $item = $this->friendLinksService->getDetail($id);
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
            'link_title' => '',
            'link_logo' => '',
            'link_url' => '',
            'sort_order/d' => 50,
        ], 'post');

        try {
            validate(FriendLinksValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->friendLinksService->createFriendLinks($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'友情链接更新失败');
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
            'link_id' => $id,
            'link_title' => '',
            'link_logo' => '',
            'link_url' => '',
            'sort_order/d' => 50,
        ], 'post');

        try {
            validate(FriendLinksValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->friendLinksService->updateFriendLinks($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'友情链接更新失败');
        }
    }

    /**
     * 更新单个字段
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['link_title', 'sort_order', 'is_show'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'link_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->friendLinksService->updateFriendLinksField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->friendLinksService->deleteFriendLinks($id);
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
                    $this->friendLinksService->deleteFriendLinks($id);
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
