<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 商品属性模板
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\ProductAttributesTplService;
use think\App;

/**
 * 商品属性模板控制器
 */
class ProductAttributesTpl extends AdminBaseController
{
    protected ProductAttributesTplService $productAttributesTplService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductAttributesTplService $productAttributesTplService
     */
    public function __construct(App $app, ProductAttributesTplService $productAttributesTplService)
    {
        parent::__construct($app);
        $this->productAttributesTplService = $productAttributesTplService;
        //$this->checkAuthor('productAttributesTplManage'); //权限检查
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
            'page' => 1,
            'size' => 15,
            'sort_field' => 'tpl_id',
        ], 'get');

        if (request()->adminType = 'shop') {
            $filter['shop_id'] = request()->shopId;
        }

        $filterResult = $this->productAttributesTplService->getFilterResult($filter);
        $total = $this->productAttributesTplService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     *
     * @return \think\Response
     */
    public function detail(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->productAttributesTplService->getDetail($id);
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
            'tpl_name' => '',
            'tpl_data' => '',
        ], 'post');

        if (request()->adminType = 'shop') {
            $data['shop_id'] = request()->shopId;
        }

        $result = $this->productAttributesTplService->updateProductAttributesTpl(0, $data, true);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('商品属性模板更新失败');
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
            'tpl_id' => $id,
            'tpl_name' => '',
            'tpl_data' => '',
        ], 'post');

        $result = $this->productAttributesTplService->updateProductAttributesTpl($id, $data, false);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('商品属性模板更新失败');
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

        if (!in_array($field, ['tpl_name'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'tpl_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->productAttributesTplService->updateProductAttributesTplField($id, $data);

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
        $this->productAttributesTplService->deleteProductAttributesTpl($id);
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
                $this->productAttributesTplService->deleteProductAttributesTpl($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
