<?php

namespace app\service\admin\product;

use app\model\product\ECard;
use app\model\product\ECardGroup;
use app\model\product\Product;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use utils\Excel;
use utils\Time;

class ECardGroupService extends BaseService
{
    protected ECardGroup $eCardGroupModel;

    public function __construct(ECardGroup $eCardGroupModel)
    {
        $this->eCardGroupModel = $eCardGroupModel;
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
        if ($filter['is_download']) {
            $fields = ['卡券名称', '卡券密码'];
            $file_name = '批量导入模板'. Time::getCurrentDatetime("Ymd") . rand(1000, 9999);
            Excel::export($fields, $file_name);
        }
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
     * 列表筛选条件
     * @param array $filter
     * @return object|\think\db\BaseQuery
     */
    public function filterQuery(array $filter): object
    {
        $query = $this->eCardGroupModel->query();
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('group_name', 'like', '%' . $filter['keyword'] . '%');
        }
        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 添加卡券分组
     * @param array $filter
     * @return ECardGroup|\think\Model
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(array $filter): ECardGroup
    {
        //查询分组名称是否存在
        $groupName = $this->eCardGroupModel->where('group_name', $filter['group_name'])->find();
        if ($groupName) {
            throw new ApiException('分组名称已存在');
        }
        return $this->eCardGroupModel->create($filter);
    }

    /**
     * 分组详情
     * @param int $id
     * @return ECardGroup
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(int $id): ECardGroup
    {
        $result = $this->eCardGroupModel::where('group_id', $id)->find();
        if (!$result) {
            throw new ApiException('分组不存在');
        }
        return $result;
    }

    /**
     * 更新
     * @param int $id
     * @param array $filter
     * @return ECardGroup
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(int $id, array $filter): ECardGroup
    {
        $item = $this->detail($id);
        $item->save($filter);
        return $item;
    }

    /**
     * 更新某个字段
     * @param int $id
     * @param array $filter
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateField(int $id, array $filter)
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }

        if (isset($filter['group_name'])) {
            //查询分组名称是否存在
            $groupName = $this->eCardGroupModel->where('group_name', $filter['group_name'])->find();
            if ($groupName) {
                throw new ApiException('分组名称已存在');
            }
        }
        $item = $this->detail($id);
        $result = $item->save($filter);
        AdminLog::add('更新电子卡券分组信息：id:' . $id);
        return $result !== false;
    }

    /**
     * 删除分组数据
     * @param int $id
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function del(int $id): bool
    {
        $find = Product::where('card_group_id', $id)
            ->where('product_status', 1)
            ->find();
        if ($find) {
            throw new ApiException('商品表中有关联该分组的上架商品，请先操作下架商品后再删除');
        }
        $result = $this->eCardGroupModel::destroy($id);
        AdminLog::add('删除电子卡券分组信息：id:' . $id);
        return $result !== false;
    }

    /**
     * 获取不分页的列表
     * @param int $shop_id
     * @return ECardGroup[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cardList(): array|\think\Collection
    {
        $shop_id = request()->shopId;
        return $this->eCardGroupModel
            ->field('group_id,group_name')
            ->where('is_use', 1)
            ->where('shop_id', $shop_id)
            ->order('group_id', 'desc')
            ->select();
    }

    /**
     * 批量导入
     * @param int $group_id
     * @return true
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function import(int $group_id)
    {
        $this->detail($group_id);
        $file_path = !empty($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : "";
        if (empty($file_path)) {
            throw new ApiException("请上传文件");
        }
        $file_row = Excel::import($file_path);
        if (empty($file_row)) {
            throw new ApiException('文件内容为空,请检查文件!');
        }

        $msg = '';
        $i = 0;
        $insert = [];
        $count = count($file_row);
        foreach ($file_row as $key => $row) {
            $model = new ECard();
            if (empty($row[0]) || empty($row[1])) {
                $i++;
                $msg .= '导入的第' . ($key + 1) . '行卡号或卡密为空,请检查!  ';
                continue;
            }
            if (
                $model->where('group_id', $group_id)
                    ->where('card_number', $row[0])
                    ->find()
            ) {
                $i++;
                $msg .= '导入的第' . ($key + 1) . '行卡号有重复,请检查!  ';
                continue;
            }
            $data['group_id'] = $group_id;
            $data['card_number'] = $row[0];
            $data['card_pwd'] = $row[1];
            $data['add_time'] = time();
            $insert[] = $data;
        }
        $ecard = new ECard();
        $ecard->insertAll($insert);
        if ($i > 0) {
            throw new ApiException('成功添加 ' . $count - $i . ' 条数据,失败 ' . $i . ' 条数据,有问题的数据如下:' . $msg);
        }
        return true;
    }
}