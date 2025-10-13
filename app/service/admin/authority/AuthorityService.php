<?php

namespace app\service\admin\authority;

use app\model\authority\AdminRole;
use app\model\authority\AdminUserVendor;
use app\model\authority\Authority;
use app\model\merchant\AdminUserShop;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;

/**
 * 商品权限服务类
 */
class AuthorityService extends BaseService
{
    protected Authority $authorityModel;

    public function __construct(Authority $authorityModel)
    {
        $this->authorityModel = $authorityModel;
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
        $result = $query->field('c.*, COUNT(s.authority_id) AS has_children')
            ->leftJoin('authority s', 'c.authority_id = s.parent_id')
            ->group('c.authority_id')->page($filter['page'], $filter['size'])->select();
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
        $query = $this->authorityModel->query()->alias('c');
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('c.authority_name', 'like', '%' . $filter['keyword'] . '%');
        }
        if (isset($filter['admin_type']) && $filter['admin_type'] != -1) {
            $query->whereIn('c.admin_type', explode(',', $filter['admin_type']));
        }
        $query->where('c.parent_id', $filter['parent_id']);
        if (config('app.IS_MERCHANT') == 0) {
            $query->whereNotIn('c.authority_sn', ['shop', 'adminMerchant']);
        }
        if ((config('app.IS_PRO') == 0)) {
            $query->whereNotIn('c.authority_sn', ['salesman']);
        }
        if ((config('app.IS_B2B') == 0)) {
            $query->whereNotIn('c.authority_sn', ['enquiryManage','userCertificationManage']);
        }

        if ((config('app.IS_VENDOR') == 0)) {
            $query->whereNotIn('c.authority_sn', ['vendor']);
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
     * @throws ApiException
     */
    public function getDetail(int $id): Authority
    {
        $result = $this->authorityModel->where('authority_id', $id)->find();

        if (!$result) {
            throw new ApiException(/** LANG */'权限不存在');
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
        return $this->authorityModel::where('authority_id', $id)->value('authority_name');
    }


    /**
     * 新增
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public function createAuthority(array $data):int
    {
        $this->authorityModel->save($data);
        AdminLog::add('新增权限:' . $data['authority_name']);
        return $this->authorityModel->getKey();
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return bool
     * @throws ApiException
     */
    public function updateAuthority(int $id, array $data):bool
    {
        $result = $this->authorityModel->where('authority_id', $id)->save($data);
        AdminLog::add('更新权限:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 检测parent_id数据问题
     * @param array $data
     * @param int $id
     * @return void
     * @throws ApiException
     */
    protected function checkParentId(array $data, int $id = 0): void
    {
        if (isset($data['parent_id'])) {
            /* 判断上级目录是否合法 */
            $children = $this->authorityAllChildIds($id,$data["auth_list"]);     // 获得当前权限的所有下级权限
            unset($children[0]);
            if ($id) {
                if (in_array($data['parent_id'], $children)) {
                    /* 选定的父类是当前权限或当前权限的下级权限 */
                    throw new ApiException(/** LANG */'所选择的上级权限不能是当前权限或者当前权限的下级权限');
                }
                if ($id == $data['parent_id']) {
                    /* 选定的父类是当前权限或当前权限的下级权限 */
                    throw new ApiException(/** LANG */'所选择的上级权限不能是当前权限');
                }
            }
        }
    }

    /**
     * 删除权限
     *
     * @param int $id
     * @return bool
     */
    public function deleteAuthority(int $id): bool
    {
        $result = $this->authorityModel::destroy($id);
        if ($result) {
            AdminLog::add('删除权限');
        }
        return $result !== false;
    }


    /**
     * 扁平化树形结构数据
     *
     * @param array $tree 嵌套的树形结构数据
     * @return array 扁平化后的数组
     */
    public function flattenTreeIterative(array $tree): array
    {
        $result = [];
        $stack = $tree;

        while (!empty($stack)) {
            $node = array_shift($stack);
            // 先提取 children
            $children = $node['children'] ?? [];
            // 删除 children 字段
            unset($node['children']);
            // 添加当前节点到结果中
            $result[] = $node;
            // 继续处理子节点
            if (!empty($children) && is_array($children)) {
                foreach (array_reverse($children) as $child) {
                    array_unshift($stack, $child);
                }
            }
        }
        return $result;
    }

    /**
     * 获取所有权限列表 新方法
     * @param int $authority_id 获取该权限id下的所有权限（不含该权限）
     * @param bool $return_ids 是否返回权限id列表
     * @return array
     */
    public function authorityList(
        int    $authority_id = 0,
        int    $type = 0,
        array  $auth_list = [],
        string $adminType = 'admin',
        int    $admin_id = 0,
        int    $shop_id = 0,
        int    $vendor_id = 0
    ): array
    {
        $cat_list = Authority::alias('c')->where('admin_type',
            $adminType)->field('c.authority_id, c.is_show,c.authority_sn,c.authority_name, c.parent_id, c.parent_id,c.authority_ico,c.route_link,c.child_auth,c.is_system,c.admin_type,c.sort_order')
            ->order('c.parent_id, c.sort_order ASC, c.authority_id ASC')->select()->toArray();

        if ($adminType == "shop" && $shop_id > 0) {
            $auth_sn = AdminUserShop::where(['admin_id' => $admin_id, 'shop_id' => $shop_id])->value('auth_list');
            if (!empty($auth_sn)) {
                if (in_array('all', $auth_sn)) {
                    $cat_list = Authority::alias('c')->where('admin_type',
                        $adminType)->field('c.authority_id, c.is_show,c.authority_sn,c.authority_name, c.parent_id, c.parent_id,c.authority_ico,c.route_link,c.child_auth,c.is_system,c.admin_type,c.sort_order')
                        ->order('c.parent_id, c.sort_order ASC, c.authority_id ASC')->select()->toArray();
                } else {
                    $cat_list = Authority::alias('c')->where('admin_type',
                        $adminType)
                        ->whereIn('authority_sn', $auth_sn)
                        ->field('c.authority_id, c.is_show,c.authority_sn,c.authority_name, c.parent_id, c.parent_id,c.authority_ico,c.route_link,c.child_auth,c.is_system,c.admin_type,c.sort_order')
                        ->order('c.parent_id, c.sort_order ASC, c.authority_id ASC')->select()->toArray();
                }

            }
        }

        if ($adminType == "vendor" && $vendor_id > 0) {

            $auth_sn = AdminUserVendor::where(['admin_id' => $admin_id, 'vendor_id' => $vendor_id])->value('auth_list');
            if (!empty($auth_sn)) {
                if (is_array($auth_sn) && in_array('all', $auth_sn) || strpos($auth_sn, 'all')) {
                    $cat_list = Authority::alias('c')->where('admin_type',
                        $adminType)->field('c.authority_id, c.is_show,c.authority_sn,c.authority_name, c.parent_id, c.parent_id,c.authority_ico,c.route_link,c.child_auth,c.is_system,c.admin_type,c.sort_order')
                        ->order('c.parent_id, c.sort_order ASC, c.authority_id ASC')->select()->toArray();
                } else {
                    $cat_list = Authority::alias('c')->where('admin_type',
                        $adminType)
                        ->whereIn('authority_sn', $auth_sn)
                        ->field('c.authority_id, c.is_show,c.authority_sn,c.authority_name, c.parent_id, c.parent_id,c.authority_ico,c.route_link,c.child_auth,c.is_system,c.admin_type,c.sort_order')
                        ->order('c.parent_id, c.sort_order ASC, c.authority_id ASC')->select()->toArray();
                }
            }
        }

        //重构数据
        $menus = [];
        foreach ($cat_list as $key => $value) {
            if ((config('app.IS_MERCHANT') == 0 && $value['authority_sn'] == 'adminMerchant') ||
                (config('app.IS_MERCHANT') == 0 && $value['authority_sn'] == 'shop') ||
                (config('app.IS_PRO') == 0 && $value['authority_sn'] == 'salesman')
                ||
                (config('app.IS_PRO') == 0 && $value['authority_sn'] == 'eCardManage')
                ||
                (config('app.IS_PRO') == 0 && $value['authority_sn'] == 'baseReceiptManage')
                ||
                (config('app.IS_PRO') == 0 && $value['authority_sn'] == 'userLevelProManage')
                ||
                (config('app.IS_OVERSEAS') == 0 && $value['authority_sn'] == 'multilingual')
                ||
                (config('app.IS_OVERSEAS') == 0 && $value['authority_sn'] == 'mobileuserUserOverseasManage')
                ||
                (config('app.IS_OVERSEAS') == 1 && $value['authority_sn'] == 'mobileUserManage')
                ||
                (config('app.IS_B2B') == 0 && $value['authority_sn'] == 'userCertificationManage')
                ||
                (config('app.IS_B2B') == 0 && $value['authority_sn'] == 'realNameAuthentication')
                ||
                (config('app.IS_B2B') == 0 && $value['authority_sn'] == 'enquiryManage')
                ||
                (config('app.IS_PRO') == 1 && $value['authority_sn'] == 'levelManageManage')
                ||
                (config('app.IS_B2B') == 0 && $value['authority_sn'] == 'userAuthenticationManage')
                ||
                (config('app.IS_B2B') == 0 && $value['authority_sn'] == 'quotationManage')
                ||
                (config('app.IS_VENDOR') == 0 && strpos($value['authority_sn'] , 'vendor')!== false)
            ) {

            } else {
                $menus[] = $value;
            }
        }
        $cat_list = $menus;
        //$res = $this->xmsbGetDataTree($cat_list, $authority_id);
        $res = $this->buildAuthorityTree($cat_list, $authority_id);

        if ($type) {
            //$res = $this->flattenTree($res);
            foreach ($res as $k=>&$auth){

                if(!empty($auth['children']) && is_array($auth['children']))
                {
                    foreach ($auth['children'] as $k2=>$childrens)
                    {
//                        if(!empty($childrens['child_auth']) && is_array($childrens['child_auth'])){
//                            $insert_array = [];
//                            $first_array = array_splice($auth['children'],0,$k2+1);
//                            foreach ($childrens['child_auth'] as $k3=>$children)
//                            {
//                                $insert_array[] = [
//                                    'authority_name' => $children['auth_name'],
//                                    'authority_sn' => $children['auth_sn']
//                                ];
//                            }
//                            $auth['children'] = array_merge($first_array,$insert_array,$auth['children']);
//                        }
                    }
                }
            }
        }

        // 处理当前角色权限
        if (!empty($auth_list) && !in_array('all', $auth_list)) {
            //var_dump($auth_list);die;
            $res = $this->filterAuthorityTree($res, $auth_list);
        }
		$res = array_values($res);

        return (array)$res;


    }


    /**
     * 过滤权限树，只保留 auth_list 中明确包含的权限
     * @param array $nodes 权限节点树
     * @param array $auth_list 用户拥有的权限 sn 列表
     * @return array
     */
    private function filterAuthorityTree(array $nodes, array $auth_list): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $sn = $node['authority_sn'] ?? '';
            if (empty($sn)) continue;

            // 递归处理子节点
            if (isset($node['children']) && is_array($node['children'])) {
                $node['children'] = $this->filterAuthorityTree($node['children'], $auth_list);
            }

            // 如果当前节点在权限列表中，则保留
            if (in_array($sn, $auth_list)) {
                // 过滤 child_auth（按钮权限）
                if (!empty($node['child_auth']) && is_array($node['child_auth'])) {
                    $node['child_auth'] = array_values(array_filter($node['child_auth'], function ($childAuth) use ($auth_list) {
                        return in_array($childAuth, $auth_list);
                    }));
                }

                $result[] = $node;
            } else if (!empty($node['children'])) {
                // 如果当前节点不在 auth_list 中，但子节点存在，则保留子节点
                $result = array_merge($result, $node['children']);
            }
        }

        return $result;
    }


    /**
     * 递归过滤权限树
     *
     * @param array $nodes 权限节点
     * @param array $auth_list 当前角色权限列表
     * @param array $parentChain 当前权限链（递归使用）
     * @return array
     */
    private function filterAuthorityTree_old(array $nodes, array $auth_list , array $parentChain = []): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $sn = $node['authority_sn'] ?? '';
            if (empty($sn)) continue;

            // 构建当前权限链
            $currentChain = array_merge($parentChain, [$sn]);

            // 检查路径上的所有父权限是否都在 auth_list 中
            $allParentInAuth = true;
            $chainCount = count($currentChain);
            for ($i = 0; $i < $chainCount - 1; $i++) {
                if (!in_array($currentChain[$i], $auth_list)) {
                    $allParentInAuth = false;
                    break;
                }
            }

            // 当前节点是否有权限
            $hasPermission = in_array($sn, $auth_list);

            // 只有当前节点有权限，且所有父权限也在 auth_list 中，才保留
            if ($hasPermission && $allParentInAuth) {
                // 递归处理子节点
                if (isset($node['children']) && is_array($node['children'])) {
                    $filteredChildren = $this->filterAuthorityTree($node['children'], $auth_list, $currentChain);
                    if (!empty($filteredChildren)) {
                        $node['children'] = $filteredChildren;
                    }
                }

                // 过滤 child_auth（按钮权限）
                if (!empty($node['child_auth']) && is_array($node['child_auth'])) {
                    $node['child_auth'] = array_values(array_filter($node['child_auth'], function ($childAuth) use ($auth_list) {
                        return in_array($childAuth, $auth_list);
                    }));
                }

                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * 修改树结构
     * @param $authorityLists
     * @return array
     */
    public function flattenTree(array $authorityLists): array
    {
        $flattenedList = [];
        foreach ($authorityLists as $key => $authority) {
            if (isset($authority['children'])) {
                foreach ($authority['children'] as $k => $v) {
                    if (isset($v['children'])) {
                        $children = $v['children'];
                        unset($authority['children'][$k]["children"]);
                        $authority["children"] = array_merge($authority['children'], $children);
                    }
                }
            }
            $flattenedList[$key] = $authority;
        }
        return $flattenedList;
    }


    /**
     * 获取指定权限id下的所有子权限id列表
     * @param int $authority_id 权限id
     * @return array
     */
    public function authorityAllChildIds(int $authority_id = 0, array $auth_list = []): array
    {
        $cat_list = $this->authorityList($authority_id,0,$auth_list);
        $ids = [$authority_id];

        $this->getChildrenIds($cat_list, $ids);

        return $ids;
    }

    public function getChildrenIds($category, &$ids)
    {
        if (!empty($category["children"])) {
            foreach ($category["children"] as $child) {
                $ids[] = $child['authority_id'];
                $this->getChildrenIds($child, $ids);
            }
        }
    }

    /**
     * 无限级权限函数
     * @param array $arr 查询出的数据
     * @param int $first_parent 根节点主键值
     * @return array
     */
    public function xmsbGetDataTree(array $arr, int $first_parent = 0): array
    {
        $tree = ['authority_id' => 0, 'parent_id' => 0];
        $tmpMap = [$first_parent => &$tree];
        foreach ($arr as $rk => $rv) {
            $tmpMap[$rv['authority_id']] = $rv;
            $parentObj = &$tmpMap[$rv['parent_id']];
            if (!isset($parentObj['children'])) {
                $parentObj['children'] = [];
            }
            $parentObj['children'][] = &$tmpMap[$rv['authority_id']];
        }
        if (!isset($tree['children'])) {
            return (array)$tree;
        }
        return (array)$tree['children'];
    }

    /**
     * 构建权限树（支持从指定 authority_id 开始）
     *
     * @param array $data 原始权限列表
     * @param int $parentId 父ID
     * @return array
     */
    public function buildAuthorityTree(array $data, int $parentId = 0): array
    {
        $result = [];

        foreach ($data as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $children = $this->buildAuthorityTree($data, (int)$item['authority_id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * 获取指定权限下的树形结构数据
     *
     * @param int $authority_id 指定权限ID
     * @return array
     */
    public function getSubAuthorityTree(int $authority_id): array
    {
        // 全部权限列表
        $allPermissions = Authority::field('authority_id,parent_id,authority_name')->select()->toArray();

        // 构建完整树结构
        $tree = $this->buildAuthorityTree($allPermissions);

        // 查找并返回指定节点及其子树
        return $this->findNodeInTree($tree, $authority_id);
    }

    /**
     * 在树中查找指定节点
     *
     * @param array $tree 树形结构
     * @param int $id 要查找的权限ID
     * @return array|null
     */
    private function findNodeInTree(array $tree, int $id): ?array
    {
        foreach ($tree as $node) {
            if ($node['authority_id'] === $id) {
                return $node;
            }

            if (!empty($node['children'])) {
                $result = $this->findNodeInTree($node['children'], $id);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }


    /**
     * 检查权限
     *
     * @param string $authority_sn
     * @return bool
     */
    public function checkAuthor(array|string $authority_sn, int $shop_id = 0, array $auth_list = []): bool
    {
        if ($shop_id == 0) {
            if (in_array('all', $auth_list)) {
                return true;
            }
            $authority_sn = is_array($authority_sn) ? $authority_sn : explode(',', $authority_sn);
            if (array_diff($authority_sn, $auth_list)) {
                throw new ApiException(/** LANG */'无此操作权限');
            }
        } else {
            // 店铺
        }
        return true;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateAuthorityField(int $id, array $data)
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = Authority::where('authority_id', $id)->save($data);
        AdminLog::add('更新权限:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 一键直达 -- 权限名称检索
     * @param string $keyword
     * @return array
     */
    public function getAuthorityList(string $keyword,string $admin_type): array
    {
        $result = [];
        if (!empty($keyword)) {
            $query = Authority::where('authority_name', 'like', '%' . $keyword . '%')
                ->where('is_show', 1)
                ->order('sort_order', 'desc')
                ->field("authority_name,route_link,authority_sn");

            if ($admin_type == "shop") {
                $query->where("admin_type", "shop");
            } elseif ($admin_type == "vendor") {
                $query->where("admin_type", "vendor");
            } elseif ($admin_type == "admin") {
                $query->where("admin_type", "admin");
            } elseif ($admin_type == "suppliers") {
				// 获取供应商的权限
				$auth_list = AdminRole::where("role_id",2)->value("authority_list");
				if (empty($auth_list)) {
					return [];
				} else {
					$query->whereIn("authority_sn", $auth_list);
				}
			}
			$result = $query->select()->toArray();
			// 去重
			$result = array_values(array_map('unserialize', array_unique(array_map('serialize', $result))));
        }
        return $result;
    }
}
