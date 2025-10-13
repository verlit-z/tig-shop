<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 秒杀活动
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\ProductTeamService;
use app\service\admin\promotion\SeckillService;
use app\validate\promotion\SeckillValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 拼团活动控制器
 */
class ProductTeam extends AdminBaseController
{
    protected ProductTeamService $service;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ProductTeamService $service
     */
    public function __construct(App $app, ProductTeamService $service)
    {
        parent::__construct($app);
        $this->service = $service;
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
            'sort_field' => 'product_team_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['shop_id'] = request()->shopId;
        $filterResult = $this->service->getFilterResult($filter);
        $total = $this->service->getFilterCount($filter);

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
        $item = $this->service->getDetail($id);
        return $this->success(
           $item
        );
    }

    /**
     * 请求数据
     * @return Response
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'product_team_name' => '',
            'start_time' => "",
            "end_time" => "",
            "limit_num" => "",
            "product_id/d" => 0,
            "items" => [],
        ], 'post');

        return $data;
    }

    /**
     * 添加
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create(): Response
    {
        $data = $this->requestData();

        try {
            validate(SeckillValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $data['shop_id'] = request()->shopId;

        $result = $this->service->create($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '活动添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["product_team_id"] = $id;
        $data['shop_id'] = request()->shopId;
        try {
            validate(SeckillValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->service->update($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '活动更新失败');
        }
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->service->delete($id);
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
            return $this->error(/** LANG */ '未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->service->delete($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */ '#type 错误');
        }
    }
}
