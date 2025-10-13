<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 角色管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\authority;

use app\model\authority\AdminRole;
use app\model\authority\AdminUser;
use app\service\common\BaseService;
use exceptions\ApiException;

/**
 * 角色管理服务类
 */
class AdminRoleService extends BaseService
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
        $query = AdminRole::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('role_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (!empty($filter['admin_type'])) {
            $query->where('admin_type', $filter['admin_type']);
        }
        if (isset($filter['merchant_id'])) {
            $query->where('merchant_id', $filter['merchant_id']);
        }
        if (isset($filter['shop_id'])) {
            $query->where('shop_id', $filter['shop_id']);
        }

        if (isset($filter['vendor_id'])) {
            $query->where('vendor_id', $filter['vendor_id']);
        }

        if (isset($filter['role_ids']) && !empty($filter['role_ids'])) {
            $query->whereIn('role_id', $filter['role_ids']);
        }


        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return AdminRole
     * @throws ApiException
     */
    public function getDetail(int $id): AdminRole
    {
        $result = AdminRole::where('role_id', $id)->find();

        if (!$result) {
            throw new ApiException('角色管理不存在');
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
        return AdminRole::where('role_id', $id)->value('role_name');
    }

    /**
     * 获取添加/更新操作通用数据
     * @param array $data
     * @return array
     */
    public function getCommunalData(array $data): array
    {
        $arr = [
            'role_name' => $data['role_name'],
            'role_desc' => $data['role_desc'],
            'authority_list' => $data["authority_list"],
//            'admin_type' => $data['admin_type'],
//            'merchant_id' => $data['merchant_id'],
            'shop_id' => $data["shop_id"] ?? 0,
        ];
        return $arr;
    }

    /**
     * 执行添加操作
     * @param array $data
     * @return int
     */
    public function createAdminRole(array $data):int
    {
        $arr = $this->getCommunalData($data);
        $result = AdminRole::create($arr);
        return $result->getKey();
    }

    /**
     * 执行更新操作
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateAdminRole(array $data, int $id):bool
    {
        $arr = $this->getCommunalData($data);
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = AdminRole::where('role_id', $id)->save($arr);
        return $result !== false;
    }

    /**
     * 删除角色管理
     *
     * @param int $id
     * @return bool
     */
    public function deleteAdminRole(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = AdminRole::destroy($id);
        //角色组删除后对应的管理员角色组也删除
        app(AdminUser::class)->where('role_id',$id)->save(['role_id' => 0]);
        return $result !== false;
    }

    /**
     * 更新单个字段
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateAdminRoleField(int $id, array $data):bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = AdminRole::where('role_id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 初始化商户角色
     * @param int merchant_id
     */
    public function initMerchantRole(int $merchantId)
    {
        $role = AdminRole::where('merchant_id', $merchantId)->find();
        if(!$role)
        {
            $data = [
                'role_name' => '超级管理员',
                'role_desc' => '系统内置角色',
                'authority_list' => ['all'],
                'admin_type' => 'shop',
                'merchant_id' => $merchantId,
            ];
            AdminRole::create($data);
        }
    }

    /**
     * 初始化供应商角色
     * @param int $vendorId
     */
    public function initVendorRole(int $vendorId)
    {
        $role = AdminRole::where('vendor_id', $vendorId)->find();
        if(!$role)
        {
            $data = [
                'role_name' => '超级管理员',
                'role_desc' => '系统内置角色',
                'authority_list' => ['all'],
                'admin_type' => 'vendor',
                'vendor_id' => $vendorId,
            ];
            AdminRole::create($data);
        }
    }
}
