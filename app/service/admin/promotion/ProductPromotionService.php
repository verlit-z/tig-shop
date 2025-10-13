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
use app\model\promotion\ProductPromotion;
use app\model\promotion\Promotion;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Time;

/**
 * 优惠活动服务类
 */
class ProductPromotionService extends BaseService
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
        $query = $this->filterQuery($filter)->append(["promotion_type_name", 'product_status', 'product_time']);
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
        $query = ProductPromotion::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('promotion_name', 'like', '%' . $filter['keyword'] . '%');
        }

        // 活动状态检索
        if (isset($filter['is_going']) && !empty($filter['is_going'])) {
            $query->productStatus($filter['is_going']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        //promotion_type
        if (isset($filter["promotion_type"]) && !empty($filter["promotion_type"])) {
            if (is_array($filter["promotion_type"])) {
                $query->whereIn('promotion_type', $filter["promotion_type"]);
            }else{
                $filter["promotion_type"] = explode(",", $filter["promotion_type"]);
                $query->whereIn('promotion_type', $filter["promotion_type"]);
            }
        }

		if (isset($filter['is_available']) && $filter['is_available'] != -1) {
			$query->where('is_available', $filter['is_available']);
		}

        //shop_id
        if (isset($filter["shop_id"])) {
            $query->where('shop_id', $filter["shop_id"]);
        }

        return $query;
    }

    /**
     * 获取活动冲突筛选
     * @param array $filter
     * @return \think\db\BaseQuery
     */
    public function getConflictQuery(array $filter): \think\db\BaseQuery
    {
        $query = ProductPromotion::query();

        //活动时间检索
        if (isset($filter['start_time'],$filter['end_time']) && !empty($filter['start_time']) && !empty($filter['end_time'])) {
            $start_time = Time::toTime($filter['start_time']);
            $end_time = Time::toTime($filter['end_time']);
            // 活动重合时间判断
            $query->where('start_time', '<=', $end_time)->where('end_time', '>=', $start_time);
        }

        // 活动范围数据
        if (isset($filter['range_data'],$filter['range']) && !empty($filter['range_data']) && $filter['range'] > 0) {
            $range_data = $filter['range_data'];
            if ($filter['range'] == ProductPromotion::PROMOTION_RANGE_PRODUCT) {
                // 指定商品
                $query->where(function ($query) use ($range_data) {
                    $conditions = [];
                    foreach ($range_data as $value) {
                        $conditions[] = "JSON_CONTAINS(range_data, JSON_ARRAY($value))";
                    }
                    $query->whereRaw(implode(' OR ', $conditions));
                });
            }
        }

        if (isset($filter["shop_id"])) {
            $query->where('shop_id', $filter["shop_id"]);
        }

        // 优惠类型区分
        if (isset($filter['promotion_type']) && !empty($filter['promotion_type'])) {
            if (in_array($filter['promotion_type'], ProductPromotion::PROMOTION_GROUP)) {
                $query->whereIn('promotion_type', ProductPromotion::PROMOTION_GROUP);
            }else{
                $query->where('promotion_type', $filter['promotion_type']);
            }
        }

        $query->where('is_available', 1);
        return $query;
    }


    /**
     * 获取冲突列表
     * @param array $filter
     * @return array
     */
    public function getConflictList(array $filter): array
    {
        $result = $this->getConflictQuery($filter)
            ->where('promotion_id',"<>",$filter['promotion_id'] ?? 0)       // 编辑时判断排除自身
            ->where('end_time',">=",Time::now())    // 未结束的活动
            ->field("promotion_id,promotion_name,promotion_type,start_time,end_time,range,range_data")
            ->append(['promotion_type_name'])
            ->select();

        // 总商品数量
        $total_product_count = Product::where(['product_status' => 1,'is_delete' => 0,'shop_id' => $filter['shop_id']])->count();

        $range_product = [];
        if (!$result->isEmpty()){
            // 获取指定商品信息
            if ($filter['range'] == ProductPromotion::PROMOTION_RANGE_PRODUCT) {
                $range_product = Product::where(['product_status' => 1,'is_delete' => 0,'shop_id' => $filter['shop_id']])
                    ->whereIn('product_id', $filter['range_data'])
                    ->field("product_id,product_name,pic_thumb")
                    ->select();

                $promotionDataByProductId = [];
                foreach ($result->toArray() as $val) {
                    foreach ($val['range_data'] as $productId) {
                        if (!isset($promotionDataByProductId[$productId])) {
                            $promotionDataByProductId[$productId] = [];
                        }
                        $promotionDataByProductId[$productId][] = $val;
                    }
                }

                // 遍历商品数据，获取对应的活动数据
                foreach ($range_product as $k => $product) {
                    $productId = $product['product_id'];
                    if (isset($promotionDataByProductId[$productId])) {
                        $product['promotions'] = $promotionDataByProductId[$productId];
                    } else {
                        $product['promotions'] = []; // 没有对应的活动数据
                    }

                    if (empty($product['promotions'])) {
                        unset($range_product[$k]);
                    }

                }

            }

            foreach ($result as $k => $item) {
                if ($item['range'] == ProductPromotion::PROMOTION_RANGE_EXCLUDE_PRODUCT) {
                    // 不含商品 -- 传参的元素与表内元素去重合并之后的数量 != 商品总量时候，才冲突
                   $range_data_count = count(array_unique(array_merge($item['range_data'], $filter['range_data'])));
                   if ($range_data_count == $total_product_count) {
                       unset($result[$k]);
                   }
                }
            }
        }

        $range_products = !empty($range_product) ? array_values($range_product->toArray()) : [];

        $total = $result->count();
        // 分页
        $result = array_slice($result->toArray(), (($filter["page"] ?? 1) - 1) * ($filter["size"] ?? 15), ($filter["size"] ?? 15));

        return ['list' => $filter['range'] == ProductPromotion::PROMOTION_RANGE_PRODUCT ? $range_products : $result, 'total' => $total];
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return ProductPromotion
     * @throws ApiException
     */
    public function getDetail(int $id): ProductPromotion
    {
        $result = ProductPromotion::where('promotion_id', $id)->append(["promotion_type_name", 'product_status', 'product_time','product_gift'])->find();

        if (!$result) {
            throw new ApiException(/** LANG */'优惠活动不存在');
        }

        return $result;
    }

    /**
     * 添加优惠活动
     * @param array $data
     * @return int
     */
    public function createProductPromotion(array $data): int
    {
        // 数据处理

        // 获取金额下限
        $data['min_order_amount'] = min(array_column($data['promotion_type_data'], 'min_amount'));
        $data['max_order_amount'] = 0;  // 默认无上限

        try {
            Db::startTrans();
            $conflictList = $this->getConflictList($data);
            if (!empty($conflictList['list'])) {
                throw new ApiException(/** LANG */ '存在冲突活动，请重新设置活动', 5001);
            }

            $data['start_time'] = isset($data['start_time']) && !empty($data['start_time']) ? Time::toTime($data['start_time']) : 0;
            $data['end_time'] = isset($data['end_time']) && !empty($data['end_time']) ? Time::toTime($data['end_time']) : 0;
            $result = ProductPromotion::create($data);

            Promotion::create([
                'type' => $this->getPromotionType((int)$data['promotion_type'] ?? 0),
                'shop_id' => $data['shop_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'promotion_name' => $data['promotion_name'],
                'relation_id' => $result->promotion_id,
                'range' => $data['range'],
                'range_data' => $data['range_data'],
                'is_available' => 1
            ]);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            throw new ApiException($e->getMessage());
        }

        return $result->getKey();
    }


    /**
     * 执行优惠活动更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateProductPromotion(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        // 获取金额下限
        $data['min_order_amount'] = min(array_column($data['promotion_type_data'], 'min_amount'));

        try {
            Db::startTrans();
            $conflictList = $this->getConflictList($data);
            if (!empty($conflictList['list'])) {
                throw new ApiException(/** LANG */ '存在冲突活动，请重新设置活动', 5001);
            }

            // 数据处理
            $data['start_time'] = isset($data['start_time']) && !empty($data['start_time']) ? Time::toTime($data['start_time']) : 0;
            $data['end_time'] = isset($data['end_time']) && !empty($data['end_time']) ? Time::toTime($data['end_time']) : 0;
            $result = ProductPromotion::where('promotion_id', $id)->save($data);

            Promotion::where([
                'type' => $this->getPromotionType((int)$data['promotion_type'] ?? 0),
                'relation_id' => $id
            ])->update([
                'shop_id' => $data['shop_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'promotion_name' => $data['promotion_name'],
                'range' => $data['range'],
                'range_data' => $data['range_data']
            ]);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            throw new ApiException($e->getMessage());
        }
        return $result !== false;
    }

    /**
     * 更新单个字段
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateProductPromotionField(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }

        if (array_key_exists('is_available', $data)) {
            $product_promotion = ProductPromotion::findOrEmpty($id);
            if ($data['is_available']) {
                // 开启 -- 验证冲突的活动
                $conflictList = $this->getConflictList($product_promotion->toArray());
                if (!empty($conflictList['list'])) {
                    throw new ApiException(/** LANG */ '存在冲突活动，请重新设置活动', 5001);
                }
            }

            // 同步 promotion 表的状态
            Promotion::where([
                'type' => $this->getPromotionType($product_promotion->promotion_type ?? 0),
                'relation_id' => $id
            ])->update(['is_available' => $data['is_available']]);
        }

        $result = ProductPromotion::where('promotion_id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 删除优惠活动
     * @param int $id
     * @return bool
     */
    public function deleteProductPromotion(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $product_promotion = ProductPromotion::findOrEmpty($id);

        if ($product_promotion->isEmpty()) {
            throw new ApiException(/** LANG */'优惠活动不存在');
        }

        if ($product_promotion->is_available) {
            throw new ApiException(/** LANG */'请先停用该活动，再删除');
        }

        $result = ProductPromotion::destroy($id);

        if ($product_promotion->promotion_type == ProductPromotion::PROMOTION_TYPE_FULL_REDUCE) {
            $type = Promotion::TYPE_PRODUCT_PROMOTION_1;
        }elseif ($product_promotion->promotion_type == ProductPromotion::PROMOTION_TYPE_FULL_DISCOUNT) {
            $type = Promotion::TYPE_PRODUCT_PROMOTION_2;
        }elseif ($product_promotion->promotion_type == ProductPromotion::PROMOTION_TYPE_FULL_REDUCE_NAME) {
            $type = Promotion::TYPE_PRODUCT_PROMOTION_3;
        }

        Promotion::where([
            'type' => $type,
            'relation_id' => $id
        ])->delete();

        return $result !== false;
    }

    /**
     * 获取优惠活动类型总表code
     * @param int $type
     * @return int
     */
    private function getPromotionType(int $type): int
    {
       if($type == 1)
       {
           $promotionType = Promotion::TYPE_PRODUCT_PROMOTION_1;
       }elseif ($type == 2)
       {
           $promotionType = Promotion::TYPE_PRODUCT_PROMOTION_2;
       }elseif ($type ==3)
       {
           $promotionType = Promotion::TYPE_PRODUCT_PROMOTION_3;
       }else{
           throw new ApiException(/** LANG */'类型错误');
       }
       return $promotionType;
    }

}
