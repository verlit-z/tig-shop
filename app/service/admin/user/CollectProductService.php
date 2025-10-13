<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 商品收藏服务层
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\user;

use app\model\product\Product;
use app\model\user\CollectProduct;
use app\service\admin\product\ProductDetailService;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Util;

/**
 * 商品收藏服务层
 */
class CollectProductService extends BaseService
{
    /**
     * 获取筛选结果
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter)
    {
        $query = $this->filterQuery($filter)->with(['product', "user", "product_sku", 'skuMinPrice']);
        $result = $query->page($filter['page'], $filter['size'])->select();
        foreach ($result as $value) {
            if ($value->sku_price > 0) $value->product_price = $value->sku_price;
            $productDetailService = new ProductDetailService($value['product_id']);
            $productAvailability = $productDetailService->getProductSkuDetail($value['product_sku']['0']['sku_id'] ?? 0,
                0,
                '');
            $value->price = $productAvailability['price'];
        }

        return $result;
    }

    /**
     * 筛选查询
     * @param array $filter
     * @return Object
     */
    public function filterQuery(array $filter): object
    {
        $query = CollectProduct::query();

        if (isset($filter["keyword"]) && !empty($filter["keyword"])) {
            $query->productName($filter["keyword"]);
        } else {
            $query->validProduct();
        }

        if (isset($filter["sort_field"], $filter["sort_order"]) && !empty($filter["sort_field"]) && !empty($filter["sort_order"])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        if (request()->userId > 0) {
            $query->where('user_id', request()->userId);
        }
        return $query;
    }

    /**
     * 取消收藏
     * @param int $id
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteCollect(int $id): bool
    {
        $collect_goods = CollectProduct::where(["product_id" => $id, "user_id" => request()->userId])->find();
        if (empty($collect_goods)) {
            throw new ApiException(/** LANG */ Util::lang('该收藏不存在'));
        }
        $result = $collect_goods->delete();
        return $result !== false;
    }

    /**
     * 收藏商品
     * @param int $product_id
     * @param int $user_id
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateCollect(int $product_id, int $user_id): bool
    {
        $product = Product::where("is_delete", 0)->where('product_id', $product_id)->find();
        if (empty($product)) {
            throw new ApiException(/** LANG */ Util::lang('该商品不存在'));
        }
        if (CollectProduct::where("product_id", $product_id)->where('user_id', $user_id)->count()) {
            throw new ApiException(/** LANG */ Util::lang('该商品已经存在于您的收藏夹中'));
        }
        $data = [
            'user_id' => $user_id,
            'product_id' => $product_id,
        ];
        $result = CollectProduct::create($data);
        return $result !== false;
    }
}
