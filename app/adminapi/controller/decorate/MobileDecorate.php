<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 首页装修模板
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\MobileDecorateService;
use app\validate\decorate\DecorateValidate as MobileDecorateValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 首页装修模板控制器
 */
class MobileDecorate extends AdminBaseController
{
    protected MobileDecorateService $mobileDecorateService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param MobileDecorateService $mobileDecorateService
     */
    public function __construct(App $app, MobileDecorateService $mobileDecorateService)
    {
        parent::__construct($app);
        $this->mobileDecorateService = $mobileDecorateService;
        $this->checkAuthor('mobileDecorate'); //权限检查
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
            'sort_field' => 'decorate_id',
            'sort_order' => 'desc',
            'decorate_type/d' => 1,
        ], 'get');
        $filterResult = $this->mobileDecorateService->getFilterResult($filter);
        $total = $this->mobileDecorateService->getFilterCount($filter);

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
        $item = $this->mobileDecorateService->getDetail($id);
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
            'decorate_title' => '',
            'decorate_type/d' => 1,
        ], 'post');

        try {
            validate(MobileDecorateValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->mobileDecorateService->createMobileDecorate($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'首页装修模板添加失败');
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
        $data = $this->request->only([
            'decorate_id' => $id,
            'decorate_title' => '',
            'decorate_type/d' => 1,
        ], 'post');

        try {
            validate(MobileDecorateValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->mobileDecorateService->updateMobileDecorate($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'首页装修模板更新失败');
        }
    }

    /**
     * 设置为首页
     * @return Response
     */
    public function setHome(): Response
    {
        $id =$this->request->all('id/d', 0);
        $result = $this->mobileDecorateService->setHome($id);
        return $this->success();
    }

    /**
     * 复制
     * @return Response
     */
    public function copy(): Response
    {
        $id =$this->request->all('id/d', 0);
        $result = $this->mobileDecorateService->copy($id);
        return $this->success();
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

        if (!in_array($field, ['decorate_title'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'decorate_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->mobileDecorateService->updateMobileDecorateField($id, $data);

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
        $this->mobileDecorateService->deleteMobileDecorate($id);
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
                    $this->mobileDecorateService->deleteMobileDecorate($id);
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
