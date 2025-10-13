<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 增票资质申请
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\finance;

use app\adminapi\AdminBaseController;
use app\service\admin\finance\UserInvoiceService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 增票资质申请控制器
 */
class UserInvoice extends AdminBaseController
{
    protected UserInvoiceService $userInvoiceService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param UserInvoiceService $userInvoiceService
     */
    public function __construct(App $app, UserInvoiceService $userInvoiceService)
    {
        parent::__construct($app);
        $this->userInvoiceService = $userInvoiceService;
        $this->checkAuthor('userInvoiceManage'); //权限检查
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
            'status/d' => -1,
            'sort_field' => 'invoice_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->userInvoiceService->getFilterResult($filter);
        $total = $this->userInvoiceService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 配置型
     * @return Response
     */
    public function config(): Response
    {
        $status_config = \app\model\finance\UserInvoice::STATUS_NAME;
        $title_type_config = \app\model\finance\UserInvoice::TITLE_TYPE_NAME;
        return $this->success([
            'status_config' => $status_config,
            'title_type_config' => $title_type_config,
        ]);
    }


    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->userInvoiceService->getDetail($id);
        return $this->success(
             $item
        );
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
            'invoice_id' => $id,
            'status/d' => 2,
            'apply_reply' => '',
        ], 'post');
        if ($data['status'] == 3 && empty($data['apply_reply'])){
            return $this->error(/** LANG */'请填写未通过原因');
        }
        $result = $this->userInvoiceService->updateQualificationApply($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'增票资质申请更新失败');
        }
    }

    /**
     * 删除
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->userInvoiceService->deleteUserInvoice($id);
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
                    $this->userInvoiceService->deleteUserInvoice($id);
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
