<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 管理员日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\authority;

use app\model\authority\AdminLog as AdminLogModel;
use app\service\common\BaseService;
use exceptions\ApiException;

/**
 * 管理员日志服务类
 */
class AdminLogService extends BaseService
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
        $query = $this->filterQuery($filter)->with(["adminUser"]);
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
        $alias = AdminLogModel::getTable();
        $query = AdminLogModel::query()->alias($alias);
        // 处理筛选条件

        // 店铺管理员日志
        if (isset($filter['admin_ids']) && !empty($filter['admin_ids'])) {
            $query->whereIn('user_id', $filter['admin_ids']);
        }

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where(function ($query) use ($filter) {
                $query->where('log_info', 'like', '%' . $filter['keyword'] . '%')
                    ->whereOr('ip_address', $filter['keyword']);
            });

        }

        if (isset($filter['user_id']) && $filter['user_id'] > 0) {
            $query->where("$alias.user_id", $filter['user_id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     * @param int $id
     * @return AdminLogModel
     * @throws ApiException
     */
    public function getDetail(int $id): AdminLogModel
    {
        $result = AdminLogModel::where('log_id', $id)->find();

        if (!$result) {
            throw new ApiException('管理员日志不存在');
        }

        return $result;
    }


    /**
     * 删除管理员日志
     *
     * @param int $id
     * @return bool
     */
    public function deleteAdminLog(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = AdminLogModel::destroy($id);
        return $result !== false;
    }

}
