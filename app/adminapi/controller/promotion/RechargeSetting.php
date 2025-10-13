<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 余额充值
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\RechargeSettingService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 余额充值控制器
 */
class RechargeSetting extends AdminBaseController
{
    protected RechargeSettingService $rechargeSettingService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param RechargeSettingService $rechargeSettingService
     */
    public function __construct(App $app, RechargeSettingService $rechargeSettingService)
    {
        parent::__construct($app);
        $this->rechargeSettingService = $rechargeSettingService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'recharge_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->rechargeSettingService->getFilterResult($filter);
        $total = $this->rechargeSettingService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->rechargeSettingService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 添加
     * @return Response
     */
    public function create()
    {
        $data = $this->request->only([
            'money' => '',
            'discount_money' => '',
            'is_show' => '',
            'sort_order/d' => 50,
        ], 'post');

        $result = $this->rechargeSettingService->createRechargeSetting($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'余额充值添加失败');
        }
    }


    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'recharge_id' => $id,
            'money' => '',
            'discount_money' => '',
            'is_show' => '',
            'sort_order/d' => 50,
        ], 'post');

        $result = $this->rechargeSettingService->updateRechargeSetting($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'余额充值更新失败');
        }
    }

    /**
     * 更新单个字段
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['sort_order', 'is_show', 'money', 'discount_money'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'recharge_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->rechargeSettingService->updateRechargeSettingField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->rechargeSettingService->deleteRechargeSetting($id);
        return $this->success();
    }

    /**
     * 批量操作
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
                    $this->rechargeSettingService->deleteRechargeSetting($id);
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
