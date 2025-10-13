<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 商品服务
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\ProductServicesService;
use think\App;

/**
 * 商品服务控制器
 */
class ProductServices extends AdminBaseController
{
    protected ProductServicesService $productServicesService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductServicesService $productServicesService
     */
    public function __construct(App $app, ProductServicesService $productServicesService)
    {
        parent::__construct($app);
        $this->productServicesService = $productServicesService;
        //$this->checkAuthor('productServicesManage'); //权限检查
    }

    /**
     * 列表页面
     *
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'default_on' => -1,
            'keyword' => '',
            'page' => 1,
            'size' => 15,
            'sort_field' => 'product_service_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->productServicesService->getFilterResult($filter);
        $total = $this->productServicesService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情页面
     *
     * @return \think\Response
     */
    public function detail(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->productServicesService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 执行添加操作
     *
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $data = $this->request->only([
            'product_service_name' => '',
            'product_service_desc' => '',
            'ico_img' => '',
            'default_on' => 1,
            'sort_order' => 50,
        ], 'post');

        $result = $this->productServicesService->updateProductServices(0, $data, true);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('商品服务更新失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return \think\Response
     */
    public function update(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'product_service_id' => $id,
            'product_service_name' => '',
            'product_service_desc' => '',
            'ico_img' => '',
            'default_on' => 1,
            'sort_order' => 50,
        ], 'post');

        $result = $this->productServicesService->updateProductServices($id, $data, false);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('商品服务更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return \think\Response
     */
    public function updateField(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['product_service_name', 'product_service_desc', 'default_on', 'sort_order'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'product_service_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->productServicesService->updateProductServicesField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $this->productServicesService->deleteProductServices($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return \think\Response
     */
    public function batch(): \think\Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error('未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            foreach ($this->request->all('ids') as $key => $id) {
                $id = intval($id);
                $this->productServicesService->deleteProductServices($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
