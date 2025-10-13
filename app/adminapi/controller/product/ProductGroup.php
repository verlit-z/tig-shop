<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 商品分组
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\ProductGroupService;
use think\App;

/**
 * 商品分组控制器
 */
class ProductGroup extends AdminBaseController
{
    protected ProductGroupService $productGroupService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductGroupService $productGroupService
     */
    public function __construct(App $app, ProductGroupService $productGroupService)
    {
        parent::__construct($app);
        $this->productGroupService = $productGroupService;
    }

    /**
     * 列表页面
     *
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'product_group_ids' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'product_group_id',
            'sort_order' => 'desc',
            'shop_id' => request()->shopId
        ], 'get');
        $filterResult = $this->productGroupService->getFilterResult($filter);
        $total = $this->productGroupService->getFilterCount($filter);

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
        $id =$this->request->all('id/d');
        $item = $this->productGroupService->getDetail($id);

        return $this->success(
           $item
        );
    }

    /**
     * 添加
     *
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $data = $this->requestData();
        $this->productGroupService->create($data);
        return $this->success();
    }

    /**
     * 执行更新
     *
     * @return \think\Response
     */
    public function update(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $this->productGroupService->edit($id, $data);
        return $this->success();
    }

    /**
     * 获取请求数据
     *
     * @return array
     */
    private function requestData(): array
    {
        $data = $this->request->only([
            'product_group_name' => '',
            'product_group_sn' => '',
            'product_group_description' => '',
            'product_ids' => [],
        ], 'post');
        $data['shop_id'] = request()->shopId;
        return $data;
    }


    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id =$this->request->all('id/d');

        if ($id) {
            $this->productGroupService->delete($id);
            return $this->success();
        } else {
            return $this->error('#id 错误');
        }
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
                $this->productGroupService->delete($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }

}
