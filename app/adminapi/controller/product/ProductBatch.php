<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 商品批量处理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\ProductBatchService;
use think\App;
use think\Response;
use think\response\Json;

class ProductBatch extends AdminBaseController
{
    protected ProductBatchService $productBatchService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductBatchService $productBatchService
     */
    public function __construct(App $app, ProductBatchService $productBatchService)
    {
        parent::__construct($app);
        $this->productBatchService = $productBatchService;
        //$this->checkAuthor('productBatchManage'); //权限检查
    }

    /**
     * 商品批量导出
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productBatchDeal(): Response
    {
        $data = $this->request->only([
            "deal_range/d" => 0,
            'range_ids' => "",
        ], 'get');

        if (request()->adminType = 'shop') {
            $data['shop_id'] = request()->shopId;
        }

        $result = $this->productBatchService->productBatchDeal($data);
        return $this->success(
            $result
        );
    }

    /**
     * 商品批量上传
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function productBatchModify(): Response
    {
        $data = $this->request->only([
            "is_auto_cat" => 0,
            "is_auto_brand" => 0,
            "is_change_pic" => 0,
            'file' => "",
        ], 'post');

        $result = $this->productBatchService->productBatchModify($data);
        return $this->success(
          $result
        );
    }

    /**
     * 批量修改商品
     * @return Response
     * @throws \think\db\exception\DbException
     */
    public function productBatchEdit(): Response
    {
        $data = $this->request->post();
        $result = $this->productBatchService->productBatchEdit($data);
        return $this->success(
            $result
        );
    }

    /**
     * 下载模版文件
     * @return \think\Response
     */
    public function downloadTemplate()
    {
        $result = $this->productBatchService->downloadTemplate();
        return $result ? $this->success() : $this->error("操作失败");
    }
}
