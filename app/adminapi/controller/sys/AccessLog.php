<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 访问日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\sys;

use app\adminapi\AdminBaseController;
use app\service\admin\sys\AccessLogService;
use think\App;

/**
 * 访问日志控制器
 */
class AccessLog extends AdminBaseController
{
    protected AccessLogService $accessLogService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AccessLogService $accessLogService
     */
    public function __construct(App $app, AccessLogService $accessLogService)
    {
        parent::__construct($app);
        $this->accessLogService = $accessLogService;
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
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->accessLogService->getFilterResult($filter);
        $total = $this->accessLogService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     *
     * @return \think\Response
     */
    public function detail(): \think\Response
    {

        $id =$this->request->all('id/d', 0);
        $item = $this->accessLogService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 执行添加操作
     *
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'access_path' => '',
            'sort_order/d' => 50,
        ], 'post');

        $result = $this->accessLogService->updateAccessLog($id, $data, true);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('访问日志更新失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return \think\Response
     */
    public function update(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'access_path' => '',
            'sort_order/d' => 50,
        ], 'post');

        $result = $this->accessLogService->updateAccessLog($id, $data, false);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('访问日志更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return \think\Response
     */
    public function updateField(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['access_path', 'sort_order'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->accessLogService->updateAccessLogField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $this->accessLogService->deleteAccessLog($id);
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
                $this->accessLogService->deleteAccessLog($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
