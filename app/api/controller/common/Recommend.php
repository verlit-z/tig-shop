<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 通用
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\common;

use app\api\IndexBaseController;
use app\service\admin\product\ProductDetailService;
use app\service\admin\product\ProductService;
use app\service\admin\user\UserService;
use think\App;
use think\Response;

/**
 * 首页控制器
 */
class Recommend extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 猜你喜欢
     *
     * @return Response
     */
    public function guessLike(): Response
    {
        $filter = $this->request->only([
            'is_show/d' => -1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'product_id',
            'sort_order' => 'desc',
            'product_id/d' => 0,
            'is_delete/d' => -1,
            'category_id/d' => 0,
            'brand_id/d' => 0,
            'product_group_id' => 0,
            'ids' => null,
            'shop_id/d' => -2, // 店铺id
            'intro_type' => '', // 商品类型
            'coupon_id' => 0,
            'shop_category_id' => -1,
            'with_cart_sum' => 0
        ], 'get');
        $filter['is_delete'] = 0;
        $filter['product_status'] = 1;
        if (isset($filter['ids']) && !empty($filter['ids'])) {
            $raw = !is_array($filter['ids']) ? $filter['ids'] : implode(',', $filter['ids']);
            $filter['sort_field_raw'] = "field(product_id," . $raw . ")";
        }
        $filterResult = app(ProductService::class)->getFilterResult($filter);
        return $this->success($filterResult);
    }


    public function getProductIds(): Response
    {
        $hotProductList = app(ProductService::class)->getProductIds([
            'intro_type' => 'hot',
            'size' => 10,
        ]);
        $bestProductList = app(ProductService::class)->getProductIds([
            'intro_type' => 'best',
            'size' => 10,
        ]);
        $resultProductList = array_merge($hotProductList, $bestProductList);
        if (request()->userId) {
            $user = app(UserService::class)->getDetail(request()->userId);
            if (!empty($user['history_product_ids'])) {
                $product_list = app(ProductService::class)->getProductIds([
                    'product_ids' => $user['history_product_ids'],
                    'size' => 20,
                ]);
                if (!empty($product_list)) {
                    $resultProductList = array_merge($product_list, $resultProductList);
                }
            }
        }
        //根据resultProductList里的product_id去重
        $resultProductList = array_values(array_column($resultProductList, null, 'product_id'));
        //取出数组里面所有的product_id
        $product_ids = array_column($resultProductList, 'product_id');
        if (count($resultProductList) < 40) {
            $product_list = app(ProductService::class)->getProductIds([
                'size' => 40 - count($resultProductList),
                'extra_product_ids' => $product_ids,
                'sort_order' => 'rand'
            ]);
            $resultProductList = array_merge($resultProductList, $product_list);
        }
        //取出数组里面所有的product_id并用逗号隔开拼接
        $ids = array_column($resultProductList, 'product_id');
        //打乱顺序
        shuffle($ids);
        $product_ids = implode(',', $ids);
        return $this->success($product_ids);
    }

}
