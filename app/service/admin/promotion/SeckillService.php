<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 秒杀活动
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
use app\model\promotion\SeckillItem;
use app\model\promotion\TimeDiscountItem;
use app\service\admin\product\ProductService;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use think\facade\Db;
use utils\Time;

/**
 * 秒杀活动服务类
 */
class SeckillService extends BaseService
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
        $query = $this->filterQuery($filter)->with(["product"])->append(["status_name"]);
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
        $query = Seckill::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('seckill_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        //shop_id
        if (isset($filter["shop_id"])) {
            $query->where('shop_id', '=', $filter["shop_id"]);
        }
        return $query;
    }

    /**
     * 获取秒杀商品列表
     *
     * @param array $params
     * @return array
     */
    public function getSeckillProductList(array $params): array
    {
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        if (!empty($params['un_started'])) {
            // 预加载seckill_items关联，但只选取product_id字段
            $seckills = Seckill::with(['seckill_item' => function ($query) {
                $query->field('*'); // 注意包含外键seckill_id以确保关联的正确性
            }])->where('seckill_start_time', '>', Time::now())
                ->paginate($params['size']);
        } else {
            // 预加载seckill_items关联，但只选取product_id字段
            $seckills = Seckill::with(['seckill_item' => function ($query) {
                $query->field('*')->order('seckill_price', 'asc'); // 注意包含外键seckill_id以确保关联的正确性
            }])->where('seckill_start_time', '<', Time::now())
                ->where('seckill_end_time', '>', Time::now())
                ->paginate($params['size']);
        }
        // 提取所有product_id
        $product_ids = [];
        $product_sales = [];
        $product_stock = [];
        $product_sales_limit = [];
        $seckills_data = [];
        $product_list = [];
        $product_sku_ids = [];
        $product_sku_sns = [];
        foreach ($seckills as $seckill) {
            $seckills_data[$seckill->product_id] = $seckill;
            $product_sales_limit[$seckill->product_id] = $seckill->seckill_limit_num;
            foreach ($seckill->seckill_item as $key => $item) {
                $product_ids[] = $item->product_id;
                $product_sales[$item->product_id] = isset($product_sales[$item->product_id]) ?
                    $product_sales[$item->product_id] + $item->seckill_sales : $item->seckill_sales;
                $product_stock[$item->product_id] = isset($product_stock[$item->product_id]) ?
                    $product_stock[$item->product_id] + $item->seckill_stock : $item->seckill_stock;
                if ($key == 0) $product_sku_ids[$item->product_id] = $item->sku_id;
                if ($key == 0) $product_sku_sns[$item->product_id] = $item->sku_sn ?: '';
            }
        }
        if ($product_ids) {
            $product_ids = array_unique($product_ids);
            $product_list = app(ProductService::class)->getProductList([
                'product_ids' => $product_ids,
                'size' => $params['size'],
            ]);
        }
        if (is_array($product_list)) {
            foreach ($product_list as &$product) {
                $product['seckill_limit_num'] = $product_sales_limit[$product['product_id']];
                $product['seckill_sales'] = (int)$product_sales[$product['product_id']];
                $product['seckill_stock'] = (int)$product_stock[$product['product_id']];
                $product['seckkill_data'] = $seckills_data[$product['product_id']];
                $product['sku_id'] = (int)$product_sku_ids[$product['product_id']];
                $product['sku_sn'] = $product_sku_sns[$product['product_id']];
                if ($product['sku_id']) {
                    foreach ($product['product_sku'] as $sku) {
                        if ($sku['sku_id'] == $product['sku_id']) {
                            $product['market_price'] = $sku['sku_price'];
                        }
                    }
                }
            }
        }
        return ['list' => $product_list, 'total' => count($product_list)];
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return Seckill
     * @throws ApiException
     */
    public function getDetail(int $id): Seckill
    {
        $result = Seckill::with(["seckill_item", "product"])->find($id);

        if (!$result) {
            throw new ApiException(/** LANG */ '秒杀活动不存在');
        }

        return $result;
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return Seckill::where('seckill_id', $id)->value('seckill_name');
    }

    /**
     * 获取秒杀活动判断
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public function getSeckillJudge(array $data): array
    {
        // 秒杀数据
        $seckill_data = [
            'seckill_name' => $data['seckill_name'],
            'seckill_start_time' => Time::toTime($data['seckill_start_time']),
            'seckill_end_time' => Time::toTime($data['seckill_end_time']),
            'seckill_limit_num' => $data['seckill_limit_num'],
            'product_id' => $data['product_id'],
        ];
        if (isset($data['shop_id'])) {
            $seckill_data['shop_id'] = $data['shop_id'];
        }
        //检测商品是否存在秒杀活动
        if ($this->checkActivityIsExist($seckill_data['product_id'], $seckill_data['seckill_start_time'], $seckill_data['seckill_end_time'],$data['seckill_id'] ?? 0)) {
            throw new ApiException(/** LANG */ '当前时间内已存在秒杀活动');
        }
        //检测商品是否存在限时折扣活动
        if ($this->checkTimeDiscountIsExist($seckill_data['product_id'], $seckill_data['seckill_start_time'], $seckill_data['seckill_end_time'])) {
            throw new ApiException(/** LANG */ '当前时间内已存在限时折扣活动');
        }
        $seckill_item_data = $data['seckill_item'];
        if (empty($seckill_item_data)) {
            throw new ApiException(/** LANG */ '请选择参加秒杀的商品');
        }
        //检测秒杀商品秒杀库存是否超出商品库存
        foreach ($seckill_item_data as $item) {
            $product_stock = app(ProductService::class)->getProductStock($data['product_id'], $item["sku_id"] ?? 0);
            if ($item['seckill_stock'] > $product_stock) {
                throw new ApiException(/** LANG */ '商品库存不足，无法添加');
            }
        }
        return $seckill_data;
    }


    /**
     * 添加秒杀活动
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function createSeckill(array $data): bool
    {
        $seckill_data = $this->getSeckillJudge($data);
        $seckill_item_data = $data['seckill_item'];
        unset($data['seckill_item']);
        $result = Seckill::create($seckill_data);
        AdminLog::add('新增秒杀活动:' . $data['seckill_name']);
        $id = $result->seckill_id;
        if ($result !== false) {
            if (!empty($seckill_item_data)) {
                $seckill_item = [];
                $sku_ids = [];
                foreach ($seckill_item_data as $key => $val) {
                    if ($val["seconds_seckill"]) {
                        if (isset($val["sku_id"])) {
                            $sku_ids[] = $val["sku_id"];
                        }
                        $seckill_item[] = [
                            "seckill_id" => $id,
                            "product_id" => $data["product_id"],
                            "sku_id" => $val["sku_id"] ?? 0,
                            "seckill_price" => $val["seckill_price"],
                            "seckill_stock" => $val["seckill_stock"],
                            'seckill_start_time' => Time::toTime($data['seckill_start_time']),
                            'seckill_end_time' => Time::toTime($data['seckill_end_time']),
                            'shop_id' => $data['shop_id']
                        ];
                    }
                }
                $res_item = (new SeckillItem)->saveAll($seckill_item);
                if (!$res_item) {
                    return false;
                }
            }
            Promotion::create([
                'type' => Promotion::TYPE_SECKILL,
                'shop_id' => $data['shop_id'],
                'start_time' => Time::toTime($data['seckill_start_time']),
                'end_time' => Time::toTime($data['seckill_end_time']),
                'promotion_name' => $data['seckill_name'],
                'relation_id' => $id,
                'range' => 3,
                'range_data' => [$data['product_id']],
                'sku_ids' => $sku_ids
            ]);
        }
        return true;
    }

    /**
     * 执行秒杀活动更新
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateSeckill(int $id, array $data): bool
    {
        $seckill_data = $this->getSeckillJudge($data);
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = Seckill::where('seckill_id', $id)->save($seckill_data);
        SeckillItem::where('seckill_id', $id)->update([
            'seckill_start_time' => Time::toTime($data['seckill_start_time']),
            'seckill_end_time' => Time::toTime($data['seckill_end_time']),
        ]);
        AdminLog::add('更新秒杀活动:' . $this->getName($id));
        Promotion::where([
            'type' => Promotion::TYPE_SECKILL,
            'relation_id' => $id
        ])->update([
            'shop_id' => $data['shop_id'],
            'start_time' => Time::toTime($data['seckill_start_time']),
            'end_time' => Time::toTime($data['seckill_end_time']),
            'promotion_name' => $data['seckill_name'],
        ]);
        return $result !== false;
    }

    /**
     * 删除秒杀活动
     *
     * @param int $id
     * @return bool
     */
    public function deleteSeckill(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $get_name = $this->getName($id);
        try {
            Db::startTrans();
            Seckill::destroy($id);
            SeckillItem::where('seckill_id', $id)->delete();
            Promotion::where([
                'type' => Promotion::TYPE_SECKILL,
                'relation_id' => $id
            ])->delete();
            Db::commit();
            AdminLog::add('新增秒杀活动:' . $get_name);
            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 获取商品秒杀活动
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
            ['seckill_start_time', '<=', $time],
            ['seckill_end_time', '>=', $time],
        ];
        $info = SeckillItem::where($where)->field('rec_id,product_id,sku_id,seckill_price,seckill_stock,seckill_sales,seckill_end_time')->findOrEmpty()->toArray();

        return $info;
    }

    /**
     * 增加秒杀销量
     * @param int $product_id
     * @param int $sku_id
     * @param int $quantity
     * @return void
     */
    public function incSales(int $product_id, int $sku_id, int $quantity): void
    {
        $activity_info = $this->getProductActivityInfo($product_id, $sku_id);
        if (!empty($activity_info)) {
            SeckillItem::where('rec_id', $activity_info['rec_id'])->inc('seckill_sales', $quantity)->update();
        }
    }

    /**
     * 减少销量
     * @param int $product_id
     * @param int $sku_id
     * @param int $quantity
     * @return void
     */
    public function decSales(int $product_id, int $sku_id, int $quantity): void
    {
        $activity_info = $this->getProductActivityInfo($product_id, $sku_id);
        if (!empty($activity_info)) {
            SeckillItem::where('rec_id', $activity_info['rec_id'])->dec('seckill_sales', $quantity)->update();
        }
    }

    /**
     * 秒杀减少库存
     * @param int $product_id
     * @param int $sku_id
     * @param int $quantity
     * @return void
     */
    public function decStock(int $product_id, int $sku_id, int $quantity): void
    {
        $activity_info = $this->getProductActivityInfo($product_id, $sku_id);
        if (!empty($activity_info)) {
            if ($activity_info['seckill_stock'] < $quantity) $quantity = $activity_info['seckill_stock'];
            SeckillItem::where('rec_id', $activity_info['rec_id'])->dec('seckill_stock', $quantity)->update();
        }
    }

    /**
     * 秒杀返回库存
     * @param int $product_id
     * @param int $sku_id
     * @param int $quantity
     * @return void
     */
    public function incStock(int $product_id, int $sku_id, int $quantity): void
    {
        $activity_info = $this->getProductActivityInfo($product_id, $sku_id);
        if (!empty($activity_info)) {
            SeckillItem::where('rec_id', $activity_info['rec_id'])->inc('seckill_stock', $quantity)->update();
        }
    }

    /**
     * 检测是否有冲突的秒杀活动
     * @param int $product_id
     * @param int $start_time
     * @param int $end_time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkActivityIsExist(int $product_id, int $start_time, int $end_time,int $seckill_id = 0): bool
    {
        $list = SeckillItem::where('product_id', $product_id)
            ->where('seckill_start_time',"<=",$end_time)
            ->where("seckill_end_time",">=",$start_time)
            ->where("seckill_id","<>",$seckill_id)
            ->select();

        if ($list->isEmpty()) {
            return false;
        }
        return true;
    }

    /**
     * 检测是否有冲突的限时折扣活动
     * @param int $product_id
     * @param int $start_time
     * @param int $end_time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkTimeDiscountIsExist(int $product_id, int $start_time, int $end_time): bool
    {
        $result = TimeDiscountItem::query()->where('product_id', $product_id)->where('start_time', '<=', $end_time)->where('end_time', '>=', $start_time)->find();

        if ($result) {
            return true;
        }
        return false;
    }
}
