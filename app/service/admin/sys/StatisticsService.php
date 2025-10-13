<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 访问日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\sys;

use app\model\sys\AccessLog;
use app\model\sys\StatisticsBase;
use app\model\sys\StatisticsLog;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;

/**
 * 访问日志服务类
 */
class StatisticsService extends BaseService
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
    protected function filterQuery(array $filter): object
    {
        $query = StatisticsLog::query();
        // 处理筛选条件

        // 店铺检索
        if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
            $query->where('shop_id', $filter['shop_id']);
        }

        // 访问时间
        if (isset($filter['access_time']) && !empty($filter['access_time'])) {
            $filter['access_time'] = is_array($filter['access_time']) ? $filter['access_time'] : explode(',',
                $filter['access_time']);
            list($start_date, $end_date) = $filter['access_time'];
            $start_date = Time::toTime($start_date);
            $end_date = Time::toTime($end_date) + 86400;
            $query->whereTime('access_time', "between", [$start_date, $end_date]);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 记录日志
     * @return bool
     */
    public function log(array $data): bool
    {

        $date = date('Y-m-d');
        $statisticsBase = StatisticsBase::where('date', $date)->find();
        if (!$statisticsBase) {
            StatisticsBase::create([
                'date' => $date,
            ]);
        }
        if (!empty($data['isNew'])) {
            StatisticsBase::where('date', $date)->inc('click_count', 1)->inc('visitor_count', 1)->save();
        } else {
            StatisticsBase::where('date', $date)->inc('click_count', 1)->save();
        }
        if (!empty($data['shopId'])) {
            $shopStatisticsBase = StatisticsBase::where('date', $date)->where('shop_id', $data['shopId'])->find();
            if (!$shopStatisticsBase) {
                StatisticsBase::create([
                    'date' => $date,
                    'shop_id' => $data['shopId'],
                ]);
            }
            if (!empty($data['isNew'])) {
                StatisticsBase::where('date', $date)->where('shop_id', $data['shopId'])->inc('click_count',
                    1)->inc('visitor_count', 1)->save();
            } else {
                StatisticsBase::where('date', $date)->where('shop_id', $data['shopId'])->inc('click_count', 1)->save();
            }
        }
        if (!empty($data['productId']) || !empty($data['shopCategoryId'])) {
            StatisticsLog::create([
                'access_time' => time(),
                'shop_id' => $data['shopId'] ?? 0,
                'product_id' => $data['productId'] ?? 0,
                'shop_category_id' => $data['shopCategoryId'] ?? 0,
                'user' => $data['user'] ?? '',
            ]);
        }
        return true;

    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function getDetail(int $id): array
    {
        $result = StatisticsLog::where('id', $id)->find();

        if (!$result) {
            throw new ApiException('访问日志不存在');
        }

        return $result->toArray();
    }


    /**
     * 执行访问日志添加或更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateAccessLog(int $id, array $data, bool $isAdd = false)
    {
        if ($isAdd) {
            $result = StatisticsLog::create($data);
            return $result->getKey();
        } else {
            if (!$id) {
                throw new ApiException('#id错误');
            }
            $result = StatisticsLog::where('id', $id)->save($data);
            return $result !== false;
        }
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateAccessLogField(int $id, array $data)
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = StatisticsLog::where('id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 删除访问日志
     *
     * @param int $id
     * @return bool
     */
    public function deleteAccessLog(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = StatisticsLog::destroy($id);


        return $result !== false;
    }

    /**
     * 获取访客量 / 浏览量
     *
     * @param array $data
     * @return int
     */
    public function getVisitNum(array $data, int $access_flag = 0, int $product_flag = 0, int $shopId = 0)
    {

        $data = StatisticsBase::where('date', '>=', $data['0'])->where('date', '<=', $data['1'])->where('shop_id',
            $shopId > 0 ? $shopId : 0)->field('sum(click_count) as click_count,sum(visitor_count) as visitor_count')->find();
        if ($access_flag) {
            // 浏览量
            return $data ? $data->click_count : 0;
        } else {
            return $data ? $data->visitor_count : 0;
        }
    }

	/**
	 * 商品访客量 / 浏览量
	 * @param array $data
	 * @param int $access_flag
	 * @param int $product_flag
	 * @param int $shopId
	 * @return int
	 */
	public function getVisitNumByProduct(array $data,int $access_flag = 0, int $product_flag = 0, int $shopId = 0):int
	{
		$query = $this->filterQuery([
			'access_time' => $data,
			'shop_id' => $shopId,
		]);

		if ($product_flag) {
			$query->where("product_id", ">", 0);
		}
		if (!$access_flag) {
			// 访客量
			$query->group('user');
		}
		return $query->count('id');
	}


    /**
     * 获取商品访问量 / 浏览量
     *
     * @param array $data
     * @return int
     */
    public function getVisitList(array $data, int $access_flag = 0, int $product_flag = 0, int $shopId = 0)
    {
        if ($access_flag) {
            // 浏览量
            $query = StatisticsBase::where('date', '>=', $data[0])->where('shop_id',
                $shopId > 0 ? $shopId : 0)->where('date', '<=',
                $data[1])->field('click_count as access_count,date as period');
        } else {
            // 访客量
            $query = StatisticsBase::where('date', '>=', $data[0])->where('shop_id',
                $shopId > 0 ? $shopId : 0)->where('date', '<=',
                $data[1])->field('visitor_count as access_count,date as period');
        }
        if ($product_flag) {
            $query = $this->filterQuery([
                'access_time' => $data,
                'shop_id' => $shopId,
            ])->field("DATE_FORMAT(FROM_UNIXTIME(access_time), '%Y-%m-%d') AS period")
                ->field("COUNT(*) AS access_count")->group('period');
            $query = $query->where("product_id", ">", 0);
        }
        return $query->select()->toArray();
    }

    /**
     * 获取访问量 / 浏览量  带去重
     *
     * @param array $data
     * @return int
     */
    public function getDistinctCount(array $data, int $access_flag = 0, int $goods_flag = 0)
    {
        if ($access_flag) {
            // 浏览量
            $query = AccessLog::field("COUNT(DISTINCT ip_address) as visit_num")->accessTime($data)->storePlatform();
            if ($goods_flag == 1) {
                $query->where("product_id", ">", 0);
            }
            return $query->select();
        } else {
            // 访客量
            return AccessLog::field("COUNT(DISTINCT ip_address) as visit_num")->accessTime($data)->storePlatform()->group('ip_address')->count();
        }
    }

}
