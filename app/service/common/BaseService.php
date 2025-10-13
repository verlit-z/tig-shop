<?php

namespace app\service\common;

use think\Model;
use think\model\Collection;

abstract class BaseService
{

    protected Model $model;

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        return $query->count();
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        return $this->model;
    }

    /**
     * @return float
     */
    public function getFilterSum(array $filter, string $column): float
    {
        $query = $this->filterQuery($filter);
        return $query->sum($column);
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterList(
        array $filter,
        array $with = [],
        array $append = [],
        array $withCount = []
    ): Collection
    {
        $query = $this->filterQuery($filter);
        if ($with) {
            $query = $query->with($with);
        }
        if ($withCount) {
            $query = $query->withCount($withCount);
        }
        if ($append) {
            $query = $query->append($append);
        }

		// 双字段排序
		if (isset($filter['sort_other_field'],$filter['sort_other_order']) && !empty($filter['sort_other_field']) && !empty($filter['sort_other_order'])) {
			$query = $query->order($filter['sort_other_field'], $filter['sort_other_order']);
		}

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query = $query->order($filter['sort_field'], $filter['sort_order']);
        }
        if (isset($filter['size']) && $filter['size'] == -1) {
            return $query->select();
        }
        return $query->page($filter['page'], $filter['size'])->select();
    }

}
