<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 积分商品
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\promotion;

use app\model\promotion\PointsExchange;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Config as UtilsConfig;
use utils\Util;

/**
 * 积分商品服务类
 */
class PointsExchangeService extends BaseService
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
        $query = $this->filterQuery($filter)->with(['product', 'productSku']);
        $result = $query->page($filter['page'], $filter['size'])->select();
        // 积分价
        foreach ($result as $item) {
            if ($item['productSku'] && $item['sku_id'] > 0) {
                $item['product_price'] = $item['productSku']['sku_price'];
            }
            $item->discounts_price = ($item->product_price - $item->points_deducted_amount) > 0 ? $item->product_price - $item->points_deducted_amount : 0;
            $item->discounts_price = Util::number_format_convert($item->discounts_price);
        }

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
        $query = PointsExchange::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->productName($filter['keyword']);
        }

        if (isset($filter['is_enabled']) && $filter["is_enabled"] != -1) {
            $query->where('is_enabled', $filter['is_enabled']);
        }

        if (isset($filter['is_hot']) && $filter['is_hot'] != -1) {
            $query->isHot($filter['is_hot']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     * @param int $id
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail(int $id): array
    {
        $result = PointsExchange::with(['product', 'productSku','skuMinPrice'])->find($id);
        if (!$result) {
            $integralName = UtilsConfig::get('integralName');
            throw new ApiException(/** LANG */ Util::lang($integralName . '商品不存在'));
        }
        $exchangeInfo = $result->toArray();
        if ($exchangeInfo['productSku']) {
            $exchangeInfo['product_price'] = $exchangeInfo['productSku']['sku_price'];
            $exchangeInfo['product_stock'] = $exchangeInfo['productSku']['sku_stock'];
        }
        $exchangeInfo['discounts_price'] = $exchangeInfo['product_price'] - $exchangeInfo['points_deducted_amount'];
        $exchangeInfo['discounts_price'] = Util::number_format_convert(max($exchangeInfo['discounts_price'], 0));
        if ($exchangeInfo['sku_id'] > 0 && empty($exchangeInfo['productSku'])) {
            //属性已变更，无属性可兑换
            $exchangeInfo['is_enabled'] = 0;
            $exchangeInfo['product_stock'] = 0;
        }
        if ($exchangeInfo['sku_id'] == 0 && $exchangeInfo['sku_price'] > 0){
            $exchangeInfo['is_enabled'] = 0;
            $exchangeInfo['product_stock'] = 0;
        }
        return $exchangeInfo;
    }

    /**
     * 获取积分兑换详情
     * @param int $product_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfoByProductId(int $product_id, int $sku_id): array
    {
        $result = PointsExchange::where([['product_id', '=', $product_id], ['sku_id', '=', $sku_id]])->find();
        if (!$result) return [];

        return $result->toArray();
    }

    /**
     * 添加积分商品
     * @param array $data
     * @return int
     */
    public function createPointsExchange(array $data): int
    {
        $result = PointsExchange::create($data);
        return $result->getKey();
    }

    /**
     * 执行积分商品更新
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updatePointsExchange(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = PointsExchange::where('id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updatePointsExchangeField(int $id, array $data)
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = PointsExchange::where('id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 删除积分商品
     *
     * @param int $id
     * @return bool
     */
    public function deletePointsExchange(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = PointsExchange::destroy($id);
        return $result !== false;
    }
}
