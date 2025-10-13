<?php

namespace app\service\admin\product;

use app\model\product\Brand;
use app\model\product\Product;
use app\service\common\BaseService;
use app\validate\product\BrandValidate;
use exceptions\ApiException;
use log\AdminLog;
use utils\Util;

/**
 * 品牌服务类
 */
class BrandService extends BaseService
{
    protected Brand $brandModel;
    protected BrandValidate $brandValidate;

    public function __construct(Brand $brandModel)
    {
        $this->brandModel = $brandModel;
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
        $query->with(['shop']);
        $query->append(['status_text']);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->brandModel->query();
        // 处理筛选条件
        if(request()->shopId == 0 && $filter['shop_id'] > 0) {
            $query->where('shop_id', $filter['shop_id']);
        }
        if(request()->shopId > 0) {
            $query->where('shop_id', request()->shopId);
        }

        if(isset($filter['status']) && $filter['status'] > -1) {
            $query->where('status', $filter['status']);
        }

        if(isset($filter['search_type']) && $filter['search_type'] == 'audit') {
            $query->where('shop_id', '>' ,  0);
        }

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('brand_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['first_word']) && !empty($filter['first_word'])) {
            $query->where('first_word', 'like', '%' . $filter['first_word'] . '%');
        }

//        if(isset($filter['search_type']) && $filter['search_type'] == 'audit') {
//            $query ->where('status', '0');
//        }

        if (isset($filter['is_show']) && $filter['is_show'] > -1) {
            $query->where('is_show', $filter['is_show']);
        }

        if (isset($filter['brand_is_hot']) && $filter['brand_is_hot'] > -1) {
            $query->where('brand_is_hot', $filter['brand_is_hot']);
        }
        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return Brand
     * @throws ApiException
     */
    public function getDetail(int $id): Brand
    {
        $result = Brand::where('brand_id', $id)->find();

        if (!$result) {
            throw new ApiException('品牌不存在');
        }

        return $result;
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return $this->brandModel::where('brand_id', $id)->value('brand_name');
    }

    /**
     * 获取品牌集合
     * @param array $ids
     * @return array|null
     */
    public function getNames(array $ids): ?array
    {
		$result = $this->brandModel::whereIn('brand_id', $ids)->group('brand_id')->column('brand_name');
        return $result ? $result : [];
    }

    /**
     * 新增
     * @param array $data
     * @param int $shopId
     * @return \think\Model|Brand
     */
    public function add(array $data, int $shopId = 0): \think\Model  | Brand
    {
        //查询该品牌名是否存在
        $check = Brand::where('brand_name', $data['brand_name'])
            ->where('shop_id', $shopId)
            ->find();
        if($check){
            throw new ApiException('该品牌名称已存在!');
        }
        if($shopId == 0) {
            $data['status'] = 1;
        }
        $data['shop_id'] = $shopId;
        //增加默认值或修改某些值
        if (empty($data['first_word'])) {
            $data['first_word'] = Util::getFirstPinyin($data['brand_name']);
        }
        return Brand::create($data);
    }
    /**
     * 执行品牌更新
     *
     * @param int $id
     * @param array $data
     * @param int $shopId
     * @return Brand
     * @throws ApiException
     */
    public function edit(int $id, array $data, int $shopId = 0): Brand
    {
        $item = $this->getDetail($id);
        if($shopId > 0) {
            $data['status'] = 0;
        }
        $check = Brand::where('brand_name', $data['brand_name'])
            ->where('shop_id', $shopId)
            ->where('brand_id', '<>', $id)
            ->find();
        if($check){
            throw new ApiException('该品牌名称已存在!');
        }
        $item->save($data);
        return $item;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @param int $shopId
     * @return int|bool
     * @throws ApiException
     */
    public function updateBrandField(int $id, array $data, int $shopId = 0)
    {
        validate(BrandValidate::class)->only(array_keys($data))->check($data);
        if (!$id) {
            throw new ApiException('#id错误');
        }
        if(isset($data['brandName'])){
            $check = Brand::where('brand_name', $data['brand_name'])
                ->where('shop_id', $shopId)
                ->where('brand_id', '<>', $id)
                ->find();
            if($check){
                throw new ApiException('该品牌名称已存在!');
            }
        }

        if($shopId > 0) {
            $data['status'] = 0;
        }
        $result = $this->brandModel::where('brand_id', $id)->save($data);
        AdminLog::add('更新品牌:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 删除品牌
     *
     * @param int $id
     * @return bool
     */
    public function deleteBrand(int $id): bool
    {
        //查询是否有商品关联该品牌id
        $check = Product::where('brand_id', $id)->find();
        if($check){
            throw new ApiException('有商品关联该品牌,请现更换品牌后再执行该操作!');
        }
        $get_name = $this->getName($id);
        $result = $this->brandModel::destroy($id);

        if ($result) {
			Product::where('brand_id', $id)->update(['brand_id' => 0]);
            AdminLog::add('删除品牌:' . $get_name);
        }

        return $result !== false;
    }

    /**
     * 批量更新品牌首字母
     *
     * @return bool
     */
    public function batchUpdateFisrtWord(): bool
    {
        $brands = $this->brandModel::field('brand_id,brand_name')->select();
        foreach ($brands as $brand) {
            $arr = ['first_word' => Util::getFirstPinyin($brand->brand_name) ?? '#'];
            $brand->save($arr);
        }
        AdminLog::add('品牌名称批量更新');
        return true;
    }

    /**
     * 获取品牌首字母列表和品牌列表
     *
     * @param string $word
     * @return array
     */
    public function getBrandWordList(string $word): array
    {
        $where = [['b.is_show', '=', 1]];
//        $brand_list = [];
//        $firstword_list = [];
//
//        if (request()->shopId) {
//            $where[] = ['a.shop_id', '=', request()->shopId];
//            $where[] = ['a.check_status', '=', 1];
//        }

        if (!empty($word)) {
            $where[] = ['b.first_word', '=', $word];
        }

        $query = Brand::alias('b')->where($where);

        $brand_list = $query->field('b.brand_id,b.brand_name,b.first_word')->select();
        $firstword_list = $query->order('b.first_word')->distinct(true)->column('b.first_word');

        return [
            'brand_list' => $brand_list,
            'firstword_list' => $firstword_list,
        ];
    }

    /**
     * 品牌审核
     * @param $params
     * @return void
     * @throws ApiException
     */
    public function audit($params)
    {
        $item = $this->getDetail($params['brand_id']);
        if(in_array($item->status, [Brand::AUDIT_PASS, Brand::AUDIT_REJECT])) {
            throw new ApiException('审核状态错误');
        }
        if($params['status'] == Brand::AUDIT_REJECT && empty($params['reject_remark'])) {
            throw new ApiException('请填写拒绝理由');
        }
        $item->save([
            'status' => $params['status'],
            'reject_remark' => $params['reject_remark'],
        ]);
        AdminLog::add('审核品牌:' . $this->getName($params['brand_id']));


    }

    public function getAuditWaitNum()
    {
        return Brand::where('status', Brand::AUDIT_WAIT)
            ->where('shop_id', '>' , 0)
            ->count();
    }
}
