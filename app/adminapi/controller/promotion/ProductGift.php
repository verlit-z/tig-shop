<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 优惠活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\model\promotion\ProductGift as ProductGiftModel;
use app\service\admin\promotion\ProductGiftService;
use app\validate\promotion\ProductGiftValidate;
use think\App;
use think\exception\ValidateException;
use think\Response;

/**
 * 活动赠品控制器
 */
class ProductGift extends AdminBaseController
{
    protected ProductGiftService $productGiftService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductGiftService $productGiftService
     */
    public function __construct(App $app, ProductGiftService $productGiftService)
    {
        parent::__construct($app);
        $this->productGiftService = $productGiftService;
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
            'gift_id' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'gift_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['shop_id'] = request()->shopId;
        $filterResult = $this->productGiftService->getFilterResult($filter);
        $total = $this->productGiftService->getFilterCount($filter);

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
        $giftId =$this->request->all('gift_id/d', 0);
        $item = ProductGiftModel::query()->where(['gift_id'=>$giftId,'shop_id'=>request()->shopId])->find();
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
            'gift_id/d' => 0,
            'gift_name' => '',
            'product_id/d' => 0,
            'sku_id/d' => 0,
            'gift_stock' => 1,
        ], 'post');
        $data['shop_id'] = request()->shopId;
        return $data;
    }


    /**
     * 添加优惠活动
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create(): Response
    {
        $data = $this->requestData();
        try {
            validate(ProductGiftValidate::class)->scene('create')->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->productGiftService->createProductGift($data);

        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'添加赠品失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $data = $this->requestData();
        try {
            validate(ProductGiftValidate::class)->scene('update')->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->productGiftService->updateProductGift($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'更新赠品失败');
        }
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $giftId=$this->request->all('id/d', 0);
        $this->productGiftService->deleteProductGift($giftId);
        return $this->success();
    }

}
