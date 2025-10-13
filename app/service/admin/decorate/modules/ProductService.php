<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 装修分类商品（宽）模块
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\decorate\modules;

use app\service\admin\product\ProductService as ProductProductService;
use app\service\common\BaseService;
use exceptions\ApiException;

/**
 * 装修服务类
 */
class ProductService extends BaseService
{
    public function __construct()
    {
    }
    /**
     * 模块数据格式化
     *
     * @param array $module
     * @return array
     * @throws ApiException
     */
    public function formatData(array $module, array $params = null, $decorate = null): array
    {
        if ($params !== null) {
            // 传了参数时才加载，此组件因为可以通过组件加载时异步获取，所以不需要初始生成数据
            $products = $module['products'];
            $page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
            $size = isset($params['size']) && $params['size'] > 0 ? $params['size'] : 10;
            if ($page == 1 && isset($products['productNumber']) && $size > $products['productNumber']) {
                $size = $products['productNumber'];
            }

            if ($products['productSelectType'] == 3) {
                if ($size * ($page - 1) > $products['productNumber']) {
                    $module['products']['productList'] = [];
                } else {
                    $filter = [
                        'intro_type' => $products['productTag'],
                        'page' => $page,
                        'size' => $size,
                    ];
                    if (!empty($decorate['shop_id'])) {
                        $filter['shop_id'] = $decorate['shop_id'];
                    }
                    $module['products']['productList'] = app(ProductProductService::class)->getProductList($filter);
                }
            } elseif ($products['productSelectType'] == 2) {

                if ($size * ($page - 1) > $products['productNumber']) {
                    $module['products']['productList'] = [];
                } else {
                    $filter = [
                        'page' => $params['page'],
                        'size' => $size,
                        'category_id' => $products['productCategoryId'],
                    ];
                    if (!empty($decorate['shop_id'])) {
                        $filter['shop_id'] = $decorate['shop_id'];
                    }
                    $module['products']['productList'] = app(ProductProductService::class)->getProductList($filter);
                }
            } elseif ($products['productSelectType'] == 1) {
                $filter = [
                    'product_ids' => $products['productIds'],
                    'page' => 1,
                    'size' => count($products['productIds'])
                ];
                if (!empty($decorate['shop_id'])) {
                    $filter['shop_id'] = $decorate['shop_id'];
                }
                if ($page == 1) {
                    $module['products']['productList'] = app(ProductProductService::class)->getProductList($filter);
                }
            }
        }
        return $module;
    }

}
