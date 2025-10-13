<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 角色管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\authority;

use app\adminapi\AdminBaseController;
use app\model\authority\AdminUserVendor;
use app\model\merchant\AdminUserShop;
use app\Request;
use app\service\admin\authority\AdminRoleService;
use app\validate\authority\AdminRoleValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 角色管理控制器
 */
class AdminRole extends AdminBaseController
{
    protected AdminRoleService $adminRoleService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AdminRoleService $adminRoleService
     */
    public function __construct(App $app, AdminRoleService $adminRoleService)
    {
        parent::__construct($app);
        $this->adminRoleService = $adminRoleService;
        $this->checkAuthor('adminRoleManage'); //权限检查
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'role_id',
            'sort_order' => 'desc',
            'shop_id' => request()->shopId
        ], 'get');


        $admin_type= request()->adminType;

        if ($admin_type == 'shop') {
            $filter['shop_id'] = request()->shopId;
            $filter['admin_type'] = 'shop';
        }elseif ($admin_type =='vendor') {
            $filter['vendor_id'] = request()->vendorId;
            $filter['admin_type'] = 'vendor';
        }else{
            $filter['admin_type'] = 'admin';
        }



        $filterResult = $this->adminRoleService->getFilterResult($filter);
        $total = $this->adminRoleService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     *
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->adminRoleService->getDetail($id);
        return $this->success($item);
    }

    /**
     * 执行添加操作
     * @return Response
     * @throws ApiException
     */
    public function create(): Response
    {
        $data = $this->request->only([
            'role_name' => '',
            'role_desc' => '',
            'authority_list' => [],
            'checkall' => '', // 是否全选
            'shop_id' => request()->shopId
        ], 'post');
        $data["merchant_id"] = request()->merchantId;

        try {
            validate(AdminRoleValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            throw new ApiException($e->getError());
        }

        $result = $this->adminRoleService->createAdminRole($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'角色管理添加失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'role_id' => $id,
            'role_name' => '',
            'role_desc' => '',
            'authority_list' => [],
            'checkall' => '', // 是否全选
            'shop_id' => request()->shopId
        ], 'post');

        try {
            validate(AdminRoleValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            throw new ApiException($e->getError());
        }

        $result = $this->adminRoleService->updateAdminRole($data, $id);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'角色管理更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id = $this->request->all('id/d', 0);
        $field = $this->request->all('field', '');

        if (!in_array($field, ['role_name', 'sort_order', 'is_show'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'role_id' => $id,
            $field => $this->request->all('val'),
        ];

        $this->adminRoleService->updateAdminRoleField($id, $data);

        return $this->success(/** LANG */'更新成功');
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id = $this->request->all('id/d', 0);
        $this->adminRoleService->deleteAdminRole($id);
        return $this->success();
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
                    $this->adminRoleService->deleteAdminRole($id);
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
