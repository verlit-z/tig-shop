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


use app\model\promotion\Promotion;
use app\model\promotion\Seckill;
use app\model\promotion\TimeDiscount;
use app\model\promotion\TimeDiscountItem;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use think\facade\Db;
use utils\Time;

/**
 * 限时折扣服务类
 */
class TimeDiscountService extends BaseService
{
    public function __construct()
    {
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
        $query = TimeDiscount::query();

        // 处理筛选条件
        if (!empty($filter['discount_id']))
        {
            $query->where('discount_id', '=', $filter['discount_id']);
        }
        // 处理筛选条件
        if (!empty($filter['promotion_name']))
        {
            $query->where('promotion_name', 'like', '%'.$filter['promotion_name'].'%');
        }
        //shop_id
        if (isset($filter["shop_id"]))
        {
            $query->where('shop_id', '=', $filter["shop_id"]);
        }

        //active_state
        if (!empty($filter["active_state"]))
        {
            $time = time();
            if($filter["active_state"] == TimeDiscount::PROMOTION_STATUS_ON)
            {
                $query->where('start_time', '<=', $time)->where('end_time', '>=', $time);
            }elseif ($filter["active_state"] == TimeDiscount::PROMOTION_STATUS_FORTHCOMING)
            {
                $query->where('start_time', '>', $time);
            }elseif ($filter["active_state"] == TimeDiscount::PROMOTION_STATUS_OFF)
            {
                $query->where('end_time', '<', $time);
            }
        }

        return $query;
    }

    /**
     * 限时折扣详情
     */
    public function detailTimeDiscount(int $discountId,int $shopId): ?array
    {
        $item['discount_info'] = TimeDiscount::with([
            'item' => [
                'product' => [
                    'productSku'
                ]
            ]
        ])->where(['discount_id' => $discountId, 'shop_id' => $shopId])->find();
        if(!$item['discount_info'])
        {
            throw new ApiException('没有此活动');
        }
        return $item;
    }

    /**
     * 添加限时折扣
     * @param array $data
     * @return void
     */
    public function createTimeDiscount(array $data): void
    {
        // 数据处理
        $data['start_time'] = isset($data['start_time']) && !empty($data['start_time']) ? Time::toTime($data['start_time']) : 0;
        $data['end_time'] = isset($data['end_time']) && !empty($data['end_time']) ? Time::toTime($data['end_time']) : 0;
        $discountItem = $data['item'];

        Db::startTrans();
        try {
            $result = TimeDiscount::create($data);
            $itemData = [];
            $rang_data = [];
            $sku_ids = [];
            foreach ($discountItem as $item)
            {
                $info['discount_id'] = $result->discount_id;
                $info['product_id'] = $item['product_id'];

                $info['start_time'] = $data['start_time'];
                $info['end_time'] = $data['end_time'];
                $info['sku_ids'] = !empty($item['sku_ids']) ? $item['sku_ids'] : [0];
                $info['value'] = $item['value'];
                $info['discount_type'] = $item['discount_type'];
                $this->checkProductPromotion($info);
                $rang_data[] = $item['product_id'];
                $itemData[] = $info;
                $sku_ids = array_merge($sku_ids, $info['sku_ids']);
            }
            TimeDiscountItem::insertAll($itemData);
            Promotion::create([
                'type' => Promotion::TYPE_PRODUCT_PROMOTION_4,
                'shop_id' => $data['shop_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'promotion_name' => $data['promotion_name'],
                'relation_id' => $result->discount_id,
                'range' => 3,
                'range_data' => $rang_data,
                'sku_ids' => $sku_ids,
            ]);
            AdminLog::add('新增限时折扣活动:' . $data['promotion_name']);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \exceptions\ApiException($e->getMessage());
        }
    }

    /**
     * 更新限时折扣
     * @param array $data
     * @return void
     */
    public function updateTimeDiscount(int $discountId,array $data): void
    {
        // 数据处理
        $data['start_time'] = isset($data['start_time']) && !empty($data['start_time']) ? Time::toTime($data['start_time']) : 0;
        $data['end_time'] = isset($data['end_time']) && !empty($data['end_time']) ? Time::toTime($data['end_time']) : 0;
        $discountItem = $data['item'];

        Db::startTrans();
        try {
            unset($data['item']);
            TimeDiscount::where('discount_id',$discountId)->update($data);
            TimeDiscountItem::where('discount_id', $discountId)->delete();
            $itemData = [];
            $rang_data = [];
            $sku_ids = [];
            foreach ($discountItem as $item) {
                $info['discount_id'] = $discountId;
                $info['product_id'] = $item['product_id'];
                $info['start_time'] = $data['start_time'];
                $info['end_time'] = $data['end_time'];
                $info['sku_ids'] = $item['sku_ids'] ?? [0];
                $info['value'] = $item['value'];
                $info['discount_type'] = $item['discount_type'];
                $this->checkProductPromotion($info);
                $rang_data[] = $item['product_id'];
                $itemData[] = $info;
                $sku_ids = array_merge($sku_ids, $info['sku_ids']);
            }

            TimeDiscountItem::insertAll($itemData);
            Promotion::where([
                'type' => Promotion::TYPE_PRODUCT_PROMOTION_4,
                'relation_id' => $discountId
            ])->update([
                'shop_id' => $data['shop_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'promotion_name' => $data['promotion_name'],
                'range' => 3,
                'range_data' => $rang_data,
                'sku_ids' => $sku_ids,
            ]);
            AdminLog::add('更新限时折扣活动:' . $data['promotion_name']);
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollback();
            throw new ApiException($e->getMessage());
        }

    }

	/**
	 * 删除限时折扣
	 * @param int $discountId
	 * @return void
	 * @throws ApiException
	 */
    public function delTimeDiscount(int $discountId): void
    {
        $discount = TimeDiscount::find($discountId);
        if ($discount)
        {
            $discount->delete();
            TimeDiscountItem::where('discount_id', $discountId)->delete();
            Promotion::where([
                'type' => Promotion::TYPE_PRODUCT_PROMOTION_4,
                'relation_id' => $discountId
            ])->delete();
        } else {
            throw new ApiException('没有此活动');
        }
    }

    /**
     * 获取商品限时折扣活动
     * @param int $product_id
     * @param int $sku_id
     * @return array
     */
    public function getProductActivityInfo(int $product_id, int $sku_id = 0): array
    {
        $time = Time::now();
        $where = [
            ['product_id', '=', $product_id],
            ['sku_id', '=', $sku_id],
            ['start_time', '<=', $time],
            ['end_time', '>=', $time],
        ];
        $info = TimeDiscountItem::where($where)->field(['start_time','end_time','value'])->find();

        return $info;
    }

    //判断商品是否参加活动
    public function checkProductPromotion(array $data):void
    {

        if (!empty($data['product_id']))
        {

            $seckill = Seckill::query()->where('product_id', $data['product_id'])->where('seckill_start_time', '<=',
                $data['end_time'])->where('seckill_end_time', '>=', $data['start_time'])->find();
            if ($seckill) {
                throw new \exceptions\ApiException('选择的商品已存在限时秒杀活动');
            }
            $discount = TimeDiscountItem::query()->where('product_id', $data['product_id'])->where('start_time', '<=',
                $data['end_time'])->where('end_time', '>=', $data['start_time'])->find();
            if ($discount) {
                throw new \exceptions\ApiException('选择的商品已存在限时折扣活动');
            }
        }

    }
}
