<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 积分日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\user;

use app\adminapi\AdminBaseController;
use app\service\admin\user\UserPointsLogService;
use app\service\admin\user\UserService;
use think\App;

/**
 * 积分日志控制器
 */
class UserPointsLog extends AdminBaseController
{
    protected UserPointsLogService $userPointsLogService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param UserPointsLogService $userPointsLogService
     */
    public function __construct(App $app, UserPointsLogService $userPointsLogService)
    {
        parent::__construct($app);
        $this->userPointsLogService = $userPointsLogService;
    }

    /**
     * 列表页面
     *
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'log_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->userPointsLogService->getFilterResult($filter);
        $total = $this->userPointsLogService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 获取当前用户积分
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function getPoints(): \think\Response
    {

        $filter = $this->request->only([
            'user_id/d' => 0,
        ], 'get');
        $user = app(UserService::class)->getDetail($filter['user_id']);
        return $this->success([
            $user['points'],
        ]);
    }

    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $this->userPointsLogService->deleteUserPointsLog($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return \think\Response
     */
    public function batch(): \think\Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error('未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            foreach ($this->request->all('ids') as $key => $id) {
                $id = intval($id);
                $this->userPointsLogService->deleteUserPointsLog($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
