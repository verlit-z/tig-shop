<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 权限
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\authority;

use app\adminapi\AdminBaseController;
use app\service\admin\authority\AuthorityService;
use app\validate\authority\AuthorityValidate;
use exceptions\ApiException;
use log\AdminLog;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 权限控制器
 */
class Authority extends AdminBaseController
{
    protected AuthorityService $authorityService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AuthorityService $authorityService
     */
    public function __construct(App $app, AuthorityService $authorityService)
    {
        parent::__construct($app);
        $this->authorityService = $authorityService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'parent_id' => 0,
            'page' => 1,
            'size' => 15,
            'sort_field' => 'c.authority_id',
            'sort_order' => 'asc',
            'admin_type' => 'admin',
        ], 'get');

        if (!in_array($filter['admin_type'], ['admin', 'shop', 'vendor'])) {
            return $this->error(/** LANG */ '#admin_type 错误');
        }

        $filterResult = $this->authorityService->getFilterResult($filter);
        $total = $this->authorityService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 获取父级权限名称
     *
     * @return Response
     */
    public function getAuthorityParentName():Response
    {
        $filter = $this->request->only([
            'parent_id' => 0,
        ], 'get');
        if ($filter['parent_id'] > 0) {
            $parent_name = $this->authorityService->getName($filter['parent_id']);
        } else {
            $parent_name = null;
        }
        return $this->success(
            $parent_name
        );
    }

    /**
     * 详情页面
     *
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d',0);
        $item = $this->authorityService->getDetail($id);
        return $this->success( 
            $item
        );
    }

    /**
     * 执行添加
     *
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->request->only([
            'authority_name' => '',
            'authority_sn' => '',
            'route_link' => '',
            'authority_ico' => '',
            'child_auth' => [],
            'parent_id' => 0,
            'is_show' => 0,
            'sort_order' => 50,
            'admin_type' => 'admin'
        ], 'post');

        try {
            validate(AuthorityValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            throw new ApiException($e->getError());
        }
        $result = $this->authorityService->createAuthority($data);
        if ($result) {
            AdminLog::add('添加权限：' . $data['authority_name']);
            return $this->success();
        } else {
            return $this->error(/** LANG */'权限更新失败');
        }
    }

    /**
     * 执行更新
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'authority_id' => $id,
            'authority_name' => '',
            'authority_sn' => '',
            'route_link' => '',
            'authority_ico' => '',
            'child_auth' => [],
            'parent_id' => 0,
            'is_show' => 0,
            'sort_order' => 50,
            'admin_type' => 'admin'
        ], 'post');
        try {
            validate(AuthorityValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            throw new ApiException($e->getError());
        }
        if ($data['authority_id'] == $data['parent_id']) {
            return $this->error(/** LANG */ '上级不能选自己');
        }
        $result = $this->authorityService->updateAuthority($id, $data);
        if ($result) {
            AdminLog::add('编辑权限：' . $data['authority_name']);
            return $this->success();
        } else {
            return $this->error(/** LANG */'权限更新失败');
        }
    }

    /**
     * 获取所有权限
     *
     * @return Response
     */
    public function getAllAuthority(): Response
    {
        $type = $this->request->all('type/d', '0');
        $admin_type = $this->request->all('admin_type', 'admin');
        $admin_id = request()->adminUid;
        $shop_id = request()->shopId;
        $vendor_id = request()->vendorId??0;
        $auth = request()->authList;
        if (!in_array($admin_type, ['admin', 'shop', 'vendor'])) {
            return $this->error(/** LANG */ '#admin_type 错误');
        }

        $cat_list = $this->authorityService->authorityList(0, $type, $auth, $admin_type, $admin_id, $shop_id, $vendor_id);

        return $this->success( 
            $cat_list
        );
    }

    public function getAuthority()
    {
        $type = $this->request->all('type/d', '0');
        $adminType = request()->adminType;
        $admin_id = request()->adminUid;
        $shop_id = request()->shopId;
        $vendor_id = request()->vendorId;
        if ($shop_id) {
            $auth = [];
        } else {
            $auth = request()->authList;
        }
        $cat_list = $this->authorityService->authorityList(0, $type, $auth, $adminType, $admin_id, $shop_id, $vendor_id);
        // 扁平化处理
        $flattenedData = $this->authorityService->flattenTreeIterative($cat_list);
        return $this->success(
            $flattenedData
        );
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id = $this->request->all('id/d');
        $field = $this->request->all('field');

        if (!in_array($field, ['authority_name', 'authority_sn', 'route_link', 'is_show', 'sort_order'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'authority_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->authorityService->updateAuthorityField($id, $data);

        return $this->success(/** LANG */'更新成功');
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id = $this->request->all('id/d');

        if ($id) {
            $this->authorityService->deleteAuthority($id);

            return $this->success();
        } else {
            return $this->error(/** LANG */'#id 错误');
        }
    }

    /**
     * 批量操作
     *
     * @return Response
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->authorityService->deleteAuthority($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }
            return $this->success();
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
