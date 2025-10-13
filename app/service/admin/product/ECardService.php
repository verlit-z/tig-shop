<?php

namespace app\service\admin\product;

use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\product\ECard;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;

class ECardService extends BaseService
{
    protected ECard $eCardModel;

    public function __construct(ECard $eCardModel)
    {
        $this->eCardModel = $eCardModel;
    }
    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        if(empty($filter['group_id'])){
            throw new ApiException('未传入分组ID');
        }
        $query = $this->filterQuery($filter);
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
        $query = $this->eCardModel
            ->query()
            ->where('group_id', $filter['group_id']);

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('card_number', 'like', '%' . $filter['keyword'] . '%');
        }
        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 添加卡券分组
     * @param array $filter
     * @return ECard|\think\Model
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(array $filter): ECard
    {
        //查询分组名称是否存在
        $groupName = $this->eCardModel->where('card_number', $filter['card_number'])->find();
        if ($groupName) {
            throw new ApiException('电子卡已存在');
        }
        return $this->eCardModel->create($filter);
    }

    /**
     * 分组详情
     * @param int $id
     * @return ECard
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(int $id): ECard
    {
        $result = $this->eCardModel::where('card_id', $id)->find();
        if (!$result) {
            throw new ApiException('电子卡券不存在');
        }
        return $result;
    }

    /**
     * 更新
     * @param int $id
     * @param array $filter
     * @return ECard
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(int $id, array $filter): ECard
    {
        $item = $this->detail($id);
        if($item->order_id > 0) {
            throw new ApiException('该卡券已被使用，无法修改!');
        }
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

        $item = $this->detail($id);
        if($item->order_id > 0) {
            throw new ApiException('该卡券已被使用，无法修改!');
        }

        if(isset($filter['card_number'])){
            //查询分组名称是否存在
            $groupName = $this->eCardModel->where('card_number', $filter['card_number'])->find();
            if ($groupName) {
                throw new ApiException('分组名称已存在');
            }
        }

        $result = $item->save($filter);
        AdminLog::add('更新电子卡券信息：id:' . $id);
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
        $item = $this->detail($id);
        if($item->order_id > 0) {
            throw new ApiException('该卡券已被使用，无法修改!');
        }
        $result = $this->eCardModel::destroy($id);
        AdminLog::add('删除电子卡券信息：id:' . $id);
        return $result !== false;
    }

    /**
     * 分组内未使用最新的卡券
     * @param int $group_id
     * @param int $limit
     * @return array
     */
    public function getNewCardByGroupId(int $group_id,int $limit = 1): array
    {
        $ecard = ECard::where(['group_id' => $group_id,'is_use' => 0])->order('card_id',"desc")->hidden(['add_time','up_time'])->limit($limit)->select()->toArray();
        return $ecard;
    }

    /**
     * 卡券分配
     * @param int $order_id
     * @return bool
     */
    public function getCardByOrder(int $order_id):bool
    {
        $order = Order::find($order_id);
        if ($order && $order->order_type == Order::ORDER_TYPE_CARD) {
            $orderItem = OrderItem::with('product')->where('order_id', $order_id)->find();
            // 获取可使用的卡券
            $e_card = app(ECardService::class)->getNewCardByGroupId($orderItem['card_group_id'],$orderItem['quantity']);

            foreach ($e_card as $e => $card) {
                $card['is_use'] = 1;
                $card['order_id'] = $order->order_id;
                $card['order_item_id'] = $orderItem['item_id'];
                $e_card[$e] = $card;
            }
            (new ECard)->saveAll($e_card);
        }
        return true;
    }

}