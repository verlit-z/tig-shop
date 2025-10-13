<?php

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\ECardService;
use exceptions\ApiException;
use think\App;
use think\Response;
class ECard extends AdminBaseController
{
    protected ECardService $eCardService;

    /**
     * 构造函数
     * @param App $app
     * @param ECardService $eCardGroupService
     */
    public function __construct(App $app, ECardService $eCardService)
    {
        parent::__construct($app);
        $this->eCardService = $eCardService;
    }

    /**
     * 列表查询
     * @return \think\Response
     */
    public function list(): Response
    {
        $group_id =$this->request->all('group_id/d', 0);
        $filter = $this->request->only([
            'is_use/d' => -1,
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'card_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['group_id'] = $group_id;
        $filterResult = $this->eCardService->getFilterResult($filter);
        $total = $this->eCardService->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 添加
     * @return \think\Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(): Response
    {
        $group_id =$this->request->all('group_id/d', 0);
        if (empty($group_id)) {
            throw new ApiException('请选择要操作的分组');
        }
        $filter = $this->request->only([
            'card_number' => '',
            'card_pwd' => '',
            'is_use/d'    => 0
        ], 'post');
        $filter['group_id'] = $group_id;
        $this->eCardService->create($filter);
        return $this->success();
    }

    /**
     * 详情
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d');
        $item = $this->eCardService->detail($id);
        return $this->success(
           $item
        );
    }
    /**
     * 更新
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $filter = $this->request->only([
            'card_number' => '',
            'card_pwd' => '',
            'is_use' => 0
        ], 'post');
        $filter['card_id'] = $id;
        $this->eCardService->update($id, $filter);
        return $this->success();
    }

    /**
     * 更新某个字段
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d');
        $field =$this->request->all('field');
        if (!in_array($field, ['card_number', 'is_use', 'card_pwd'])) {
            return $this->error('#field 错误');
        }
        $filter = [
            'card_id' => $id,
            $field =>$this->request->all('val'),
        ];
        $this->eCardService->updateField($id, $filter);
        return $this->success();
    }

    /**
     * 删除
     * @return Response
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function del()
    {
        $id =$this->request->all('id/d');
        if($id) {
            $this->eCardService->del($id);
            return $this->success();
        } else {
            return $this->error('#id 错误');
        }
    }
}