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

use app\model\promotion\ProductTeam;
use app\model\promotion\ProductTeamItem;
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
 * 拼团服务类
 */
class ProductTeamService extends BaseService
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
        $query = ProductTeam::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('product_team_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        //shop_id
        if (isset($filter["shop_id"]) && $filter["shop_id"] > -1) {
            $query->where('shop_id', '=', $filter["shop_id"]);
        }
        return $query;
    }

    /**
     * 获取商品列表
     *
     * @param array $params
     * @return array
     */
    public function getProductList(array $params): array
    {
        if (empty($params['page'])) {
            $params['page'] = 1;
        }

        return [];
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
        $result = ProductTeam::with(["items", "product"])->find($id);

        if (!$result) {
            throw new ApiException(/** LANG */ '活动不存在');
        }

        return $result;
    }


    /**
     * 获取拼团活动判断
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public function getJudge(array $data): array
    {
        // 数据
        $data = [
            'product_team_name' => $data['product_team_name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['start_time'],
            'limit_num' => $data['limit_num'],
            'product_id' => $data['product_id'],
        ];
        if (isset($data['shop_id'])) {
            $data['shop_id'] = $data['shop_id'];
        }
        //检测商品是否存在秒杀活动
        if ($this->checkActivityIsExist($data['product_id'], $data['start_time'], $data['end_time'],
            $data['product_team_id'] ?? 0)) {
            throw new ApiException(/** LANG */ '当前时间内已存在拼团活动');
        }
        $item_data = $data['items'];
        if (empty($item_data)) {
            throw new ApiException(/** LANG */ '请选择参加拼团的商品');
        }

        return $data;
    }


    /**
     * 添加拼团数据
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function create(array $data): bool
    {
        $data = $this->getJudge($data);
        $item_data = $data['items'];
        unset($data['items']);
        $result = ProductTeam::create($data);
        $id = $result->product_team_id;
        if ($result !== false) {
            if (!empty($item_data)) {
                $item = [];
                foreach ($item_data as $key => $val) {
                    $item[] = [
                        "product_team_id" => $id,
                        "product_id" => $data["product_id"],
                        "sku_id" => $val["sku_id"] ?? 0,
                        "price" => $val["price"],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'shop_id' => $data['shop_id']
                    ];
                }
                $res_item = (new ProductTeamItem())->saveAll($item);
                if (!$res_item) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 执行活动更新
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->getJudge($data);
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = ProductTeam::where('product_team_id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 删除
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        try {
            Db::startTrans();
            ProductTeam::destroy($id);
            ProductTeamItem::where('product_team_id', $id)->delete();

            Db::commit();
            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 获取商品活动
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
        $info = ProductTeamItem::where($where)->findOrEmpty()->toArray();

        return $info;
    }


    /**
     * 检测是否有冲突的活动
     * @param int $product_id
     * @param int $start_time
     * @param int $end_time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkActivityIsExist(
        int $product_id,
        int $start_time,
        int $end_time,
        int $product_team_id = 0
    ): bool {
        $list = SeckillItem::where('product_id', $product_id)
            ->where('start_time', "<=", $end_time)
            ->where("end_time", ">=", $start_time)
            ->where("product_team_id", "<>", $product_team_id)
            ->select();

        if ($list->isEmpty()) {
            return false;
        }
        return true;
    }


}
