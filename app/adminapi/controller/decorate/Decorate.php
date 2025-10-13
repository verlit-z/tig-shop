<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 装修
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\DecorateService;
use app\validate\decorate\DecorateValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 装修控制器
 */
class Decorate extends AdminBaseController
{
    protected DecorateService $decorateService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param DecorateService $decorateService
     */
    public function __construct(App $app, DecorateService $decorateService)
    {
        parent::__construct($app);
        $this->decorateService = $decorateService;
    }

    /**
     * 列表页面
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'decorate_type/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'decorate_id',
            'sort_order' => 'desc',
            'parent_id' => 0
        ], 'get');
        $filter['shop_id'] = request()->shopId;
        if ($filter['decorate_type'] == 1) {
            $this->checkAuthor('pcDecorateManage');
        } elseif ($filter['decorate_type'] == 2) {
            $this->checkAuthor('mobileDecorateManage');
        }
        $filterResult = $this->decorateService->getFilterList($filter, ['locale', 'children.bindLocaleName']);
        $total = $this->decorateService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function detail(): Response
    {
        $id = $this->request->all('id/d', 0);
        if (empty($id)) {
            $decorate = \app\model\decorate\Decorate::where('decorate_type', $this->request->all('decorate_type/d', 1))
                ->where('parent_id', $this->request->all('parent_id/d', 0))
                ->where('locale_id', $this->request->all('locale_id/d', 0))
                ->find();
            if ($decorate) {
                $id = $decorate['decorate_id'];
            }
        }
        $item = $this->decorateService->getDetail($id, ['children.bindLocaleName']);
        if ($item) {
            $this->checkShopAuth($item['shop_id']);
        }
        return $this->success($item);
    }

    /**
     * 获取草稿数据
     * @return Response
     */
    public function loadDraftData(): Response
    {
        $id = $this->request->all('id/d', 0);
        $item = $this->decorateService->getDetail($id);
        $this->checkShopAuth($item['shop_id']);
        return $this->success(
            $item['draft_data'] ?? []
        );
    }

    /**
     * 存入草稿
     *
     * @return Response
     */
    public function saveDraft(): Response
    {
        $id = $this->request->all('id/d', 0);
        $data = $this->request->only([
            'decorate_id' => $id,
            'data' => '',
            'locale_id' => 0,
            'parent_id' => 0
        ], 'post');
        $item = $this->decorateService->getDetail($id);
        if ($item) {
            $this->checkShopAuth($item['shop_id']);
        }
        $result = $this->decorateService->saveDecoratetoDraft($id, $data['data'], $data);
        return $this->success();
    }

    /**
     * 发布
     * @return Response
     * @throws ApiException
     */
    public function publish(): Response
    {
        $id = $this->request->all('id/d', 0);
        $data = $this->request->only([
            'decorate_type/d' => 1,
            'locale_id' => 0,
            'parent_id' => 0,
            'data' => '',
        ], 'post');
        $item = $this->decorateService->getDetail($id);
        if ($item) {
            $this->checkShopAuth($item['shop_id']);
        }
        $data['status'] = 1;
        $data['draft_data'] = '';
        $result = $this->decorateService->updateDecorate($id, $data);
        return $this->success();
    }

    /**
     * 复制
     * @return Response
     */
    public function copy(): Response
    {
        $id = $this->request->all('id/d', 0);
        $result = $this->decorateService->copy($id);
        return $this->success();
    }

    /**
     * 设置为首页
     * @return Response
     */
    public function setHome(): Response
    {
        $id = $this->request->all('id/d', 0);
        $result = $this->decorateService->setHome($id);
        return $this->success();
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
            'data' => '',
            'shop_id' => request()->shopId,
            'parent_id' => 0,
            'locale_id' => 0
        ], 'post');

        try {
            validate(DecorateValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->decorateService->createDecorate($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'装修添加失败');
        }
    }



    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id = $this->request->all('id/d', 0);
        $data = $this->request->only([
            'decorate_id' => $id,
            'decorate_title' => '',
            'decorate_type/d' => 1,
            'locale_id' => 0,
            'parent_id' => 0,
            'data' => '',
        ], 'post');
        $item = $this->decorateService->getDetail($id);
        if ($item) {
            $this->checkShopAuth($item['shop_id']);
            try {
                validate(DecorateValidate::class)
                    ->scene('update')
                    ->check($data);
            } catch (ValidateException $e) {
                return $this->error($e->getError());
            }
        }


        $result = $this->decorateService->updateDecorate($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'装修更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id = $this->request->all('id/d', 0);
        $field = $this->request->all('field', '');
        $item = $this->decorateService->getDetail($id);
        $this->checkShopAuth($item['shop_id']);
        if (!in_array($field, ['decorate_title', 'is_show', 'sort_order'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'decorate_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->decorateService->updateDecorateField($id, $data);

        return $this->success(/** LANG */'更新成功');
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->decorateService->getDetail($id);
        $this->checkShopAuth($item['shop_id']);
        $this->decorateService->deleteDecorate($id);
        return $this->success(/** LANG */'指定项目已删除');
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
                    $item = $this->decorateService->getDetail($id);
                    $this->checkShopAuth($item['shop_id']);
                    $id = intval($id);
                    $this->decorateService->deleteDecorate($id);
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
