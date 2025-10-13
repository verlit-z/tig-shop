<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 品牌
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\BrandService;
use app\validate\product\BrandValidate;
use think\App;
use think\exception\ValidateException;

/**
 * 品牌控制器
 */
class Brand extends AdminBaseController
{
    protected BrandService $brandService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param BrandService $brandService
     */
    public function __construct(App $app, BrandService $brandService)
    {
        parent::__construct($app);
        $this->brandService = $brandService;
    }

    /**
     * 列表页面
     *
     * @return \think\Response
     */
    public function list(): \think\Response
    {
        $filter = $this->request->only([
            'first_word' => '',
            'is_show/d' => -1,
            'brand_is_hot/d' => -1,
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'brand_id',
            'sort_order' => 'desc',
            'shop_id' => 0,
            'status' => -1
        ], 'get');
        $filterResult = $this->brandService->getFilterResult($filter);
        $total = $this->brandService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 选择品牌列表页面
     *
     * @return \think\Response
     */
    public function search(): \think\Response
    {
        $word =$this->request->all('word', '');
        $res = $this->brandService->getBrandWordList($word);

        return $this->success([
            'brand_list' => $res['brand_list'],
            'firstWord_list' => $res['firstword_list'],
        ]);
    }

    /**
     * 编辑页面
     *
     * @return \think\Response
     */
    public function detail(): \think\Response
    {
        $id =$this->request->all('id/d');
        $item = $this->brandService->getDetail($id);

        return $this->success(
           $item
        );
    }
    /**
     * 添加
     *
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $data = $this->requestData();
        try {
            validate(BrandValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $shopId = request()->shopId;
        $result = $this->brandService->add($data, $shopId);
        if($shopId > 0) {
            return $this->success('品牌添加成功,等待管理员审核!');
        } else {
            return $this->success('品牌添加成功');
        }

    }

    /**
     * 执行更新
     *
     * @return \think\Response
     */
    public function update(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data['brand_id'] = $id;
        $shopId = request()->shopId;
        try {
            validate(BrandValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->brandService->edit($id, $data, $shopId);
        if($shopId > 0) {
            return $this->success('修改成功,等待管理员审核!');
        } else {
            return $this->success('修改成功');
        }
    }

    /**
     * 获取请求数据
     *
     * @return array
     */
    private function requestData(): array
    {
        $data = $this->request->only([
            'brand_name' => '',
            'first_word' => '',
            'brand_type' => '',
            'brand_desc' => '',
            'brand_logo' => '',
            'brand_is_hot' => 0,
            'is_show' => 1,
            'sort_order' => 50,
        ], 'post');

        return $data;
    }
    /**
     * 更新单个字段
     *
     * @return \think\Response
     */
    public function updateField(): \think\Response
    {
        $id = $this->request->all('id/d');
        $field =$this->request->all('field');
        $shopId = request()->shopId;
        if (!in_array($field, ['brand_name', 'first_word', 'brand_is_hot', 'is_show', 'sort_order'])) {
            return $this->error('#field 错误');
        }

        $data = [
            'brand_id' => $id,
            $field =>$this->request->all('val'),
        ];
        try {
            validate(BrandValidate::class)
                ->only(array_keys($data))
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $this->brandService->updateBrandField($id, $data, $shopId);
        if($shopId > 0) {
            return $this->success('修改成功,等待管理员审核!');
        } else {
            return $this->success();
        }
    }

    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id = $this->request->all('id/d');

        if ($id) {
            $this->brandService->deleteBrand($id);
            return $this->success();
        } else {
            return $this->error('#id 错误');
        }
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
                $this->brandService->deleteBrand($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }

    //批量更新首字母
    public function updateFirstWord()
    {
        $this->brandService->batchUpdateFisrtWord();
        return $this->success();
    }

    /**
     * 审核列表
     * @return \think\Response
     */
    public function auditList() : \think\Response
    {
        $filter = $this->request->only([
            'first_word' => '',
            'is_show/d' => -1,
            'brand_is_hot/d' => -1,
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'brand_id',
            'sort_order' => 'desc',
            'search_type' => 'audit',
            'status' => -1,
            'shop_id/d'=>0,
        ], 'get');
//        $shopId = request()->shopId;
//        $filter['shop_id'] = $shopId;
        $filterResult = $this->brandService->getFilterResult($filter);
        $total = $this->brandService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 品牌审核
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function audit(): \think\Response
    {
        $filter = $this->request->only([
            'brand_id/d' => 0,
            'status' => 0,
            'reject_remark' => '',
        ], 'post');
        $this->brandService->audit($filter);
        return $this->success('审核成功');
    }

    /**
     * 获取待审核数量
     * @return \think\Response
     */
    public function auditWaitNum(): \think\Response
    {
        $total = $this->brandService->getAuditWaitNum();
        return $this->success(
            $total,
        );
    }
}
