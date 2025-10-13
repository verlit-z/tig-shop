<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 运费模板
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\ShippingTplService;
use app\validate\setting\ShippingTplValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 运费模板控制器
 */
class ShippingTpl extends AdminBaseController
{
    protected ShippingTplService $shippingTplService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ShippingTplService $shippingTplService
     */
    public function __construct(App $app, ShippingTplService $shippingTplService)
    {
        parent::__construct($app);
        $this->shippingTplService = $shippingTplService;
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
            'sort_field' => 'shipping_tpl_id',
            'sort_order' => 'desc',
        ], 'get');

        $filter['shop_id'] = request()->shopId;

        $filterResult = $this->shippingTplService->getFilterResult($filter);
        $total = $this->shippingTplService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 配置型
     * @return Response
     */
    public function config(): Response
    {
        $shipping_tpl_info = $this->shippingTplService->getShippingTplInfo(0, request()->shopId ?? 0);
        return $this->success(
            $shipping_tpl_info
        );
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $shipping_tpl_info = $this->shippingTplService->getShippingTplInfo($id, request()->shopId ?? 0);
        $item = $this->shippingTplService->getDetail($id);
        $item['shipping_tpl_info'] = $shipping_tpl_info;
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
            'shipping_tpl_name' => '',
            'shipping_time' => '',
            'is_free/d' => 0,
            'pricing_type/d' => 1,
            'is_default/d' => 0,
            'shipping_tpl_info' => [],
        ], 'post');

        return $data;
    }

    /**
     * 添加操作
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create(): Response
    {
        $data = $this->requestData();

        try {
            validate(ShippingTplValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $data["shop_id"] = request()->shopId;
        $result = $this->shippingTplService->createShippingTpl($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '运费模板添加失败');
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
        $data["shipping_tpl_id"] = $id;

        try {
            validate(ShippingTplValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->shippingTplService->updateShippingTpl($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '运费模板更新失败');
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

        if (!in_array($field, ['shipping_tpl_name', 'sort_order', 'is_show'])) {
            return $this->error(/** LANG */ '#field 错误');
        }

        $data = [
            'shipping_tpl_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->shippingTplService->updateShippingTplField($id, $data);

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
        $this->shippingTplService->deleteShippingTpl($id);
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
            return $this->error(/** LANG */ '未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->shippingTplService->deleteShippingTpl($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */ '#type 错误');
        }
    }
}
