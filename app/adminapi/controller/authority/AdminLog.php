<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 管理员日志
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
use app\service\admin\authority\AdminLogService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 管理员日志控制器
 */
class AdminLog extends AdminBaseController
{
    protected AdminLogService $adminLogService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AdminLogService $adminLogService
     */
    public function __construct(App $app, AdminLogService $adminLogService)
    {
        parent::__construct($app);
        $this->adminLogService = $adminLogService;
        $this->checkAuthor('adminLogManage'); //权限检查
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
            'user_id/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'log_id',
            'sort_order' => 'desc',
        ], 'get');


        $admin_type= request()->adminType;

        if ($admin_type == 'shop') {
            $filter['shop_id'] = request()->shopId;
            $filter['admin_ids'] = AdminUserShop::where(['shop_id' => $filter['shop_id']])->column('admin_id');
        }elseif ($admin_type =='vendor') {
            $filter['vendor_id'] = request()->vendorId;
            $filter['admin_ids'] = AdminUserVendor::where(['vendor_id' => $filter['vendor_id']])->column('admin_id');
        }

//        if (request()->shopId > 0) {
//            $filter['shop_id'] = request()->shopId;
//            $filter['admin_ids'] = AdminUserShop::where(['shop_id' => $filter['shop_id']])->column('admin_id');
//        }

        $filterResult = $this->adminLogService->getFilterResult($filter);
        $total = $this->adminLogService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->adminLogService->deleteAdminLog($id);
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
            return $this->error('未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->adminLogService->deleteAdminLog($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
