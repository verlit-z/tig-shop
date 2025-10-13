<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 优惠活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\promotion;


use app\model\product\Product;
use app\model\product\ProductSku;
use app\model\promotion\ProductGift;
use app\model\promotion\ProductPromotion;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;

/**
 * 活动赠品服务类
 */
class ProductGiftService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = ProductGift::query();

        // 处理筛选条件
        if (!empty($filter['gift_id']))
        {
            $query->where('gift_id', '=', $filter['gift_id']);
        }
        // 处理筛选条件
        if (!empty($filter['keyword']))
        {
            $query->where('gift_name', 'like', '%'.$filter['keyword'].'%');
        }
        //shop_id
        if (isset($filter["shop_id"]))
        {
            $query->where('shop_id', '=', $filter["shop_id"]);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        return $query;
    }

    /**
     * 赠品判断
     * @param array $data
     * @return void
     * @throws ApiException
     */
    public function judgmentProductGift(array $data): void
    {
        $gift = ProductGift::where(['product_id' => $data['product_id'],'sku_id' => $data['sku_id'],'shop_id' => $data['shop_id']])
            ->where("gift_id", '<>', $data['gift_id'])
            ->find();
        if ($gift) {
            throw new ApiException(/** LANG */'不能重复添加');
        }
        if ($data['sku_id'] > 0) {
            $skuStock = ProductSku::where('sku_id', $data['sku_id'])->value('sku_stock');
            if ($data['gift_stock'] > $skuStock) {
                throw new ApiException(/** LANG */'赠品库存不能超过商品规格库存');
            }
        } else {
            $product_stock = Product::find($data['product_id'])->product_stock ?? 0;
            if ($data['gift_stock'] > $product_stock) {
                throw new ApiException(/** LANG */'赠品库存不能超过商品库存');
            }
        }
    }

    /**
     * 创建赠品
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function createProductGift(array $data):bool
    {
        $this->judgmentProductGift($data);
        $result = ProductGift::create($data);
        return $result !== false;
    }

    /**
     * 更新赠品
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateProductGift(array $data): bool
    {
        $gift = ProductGift::find($data['gift_id']);
        if (empty($gift)) {
            throw new ApiException(/** LANG */'赠品不存在');
        }
        $this->judgmentProductGift($data);
        $result = $gift->save($data);
        return $result !== false;
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     * @throws ApiException
     */
    public function deleteProductGift(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $gift_detail = ProductGift::findOrEmpty($id);

        if ($gift_detail->isEmpty()) {
            throw new ApiException(/** LANG */'该赠品不存在');
        }

        // 在已结束的满赠活动 / 未参与活动的 赠品可删除
        $valid_promotion = ProductPromotion::where([
                'is_available' => 1,
                'shop_id' => $gift_detail->shop_id,
                'promotion_type' => ProductPromotion::PROMOTION_TYPE_FULL_REDUCE_NAME
            ])
            ->where("end_time",">=",Time::now())
            ->select()->toArray();

        foreach ($valid_promotion as $item) {
            $gift_ids = array_column($item['promotion_type_data'], 'gift_id');
            if (in_array($id, $gift_ids)) {
                throw new ApiException(/** LANG */'该赠品已被使用，不可删除');
            }
        }

        $result = $gift_detail->delete();

        return $result !== false;
    }

    /**
     * 减库存
     * @param $gift_id
     * @param $quantity
     * @return bool
     */
    public function decStock($gift_id, $quantity): bool
    {
        return ProductGift::where('gift_id', '=', $gift_id)->dec('gift_stock', $quantity)->update();
    }
}
