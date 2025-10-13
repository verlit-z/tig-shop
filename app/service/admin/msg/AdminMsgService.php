<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 管理员消息
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\msg;

use app\model\msg\AdminMsg;
use app\model\product\Product;
use app\service\common\BaseService;
use exceptions\ApiException;

/**
 * 示例模板服务类
 */
class AdminMsgService extends BaseService
{
    public function __construct()
    {
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
    public function filterQuery(array $filter): object
    {

        $query = AdminMsg::query();

        // 处理筛选条件
		if (isset($filter['suppliers_id']) && !empty($filter['suppliers_id']) && isset($filter['suppliers_type']) && !empty($filter['suppliers_type'])) {
			// 订单包含 + 商品对应
			$suppliers_id = $filter['suppliers_id'];
			if ($filter['suppliers_type'] == 1) {
				$product_ids = Product::where(['suppliers_id' => $suppliers_id,'is_delete' => 0])->column('product_id');
				$query->hasWhere("items", function ($query) use ($product_ids) {
					$query->whereIn('product_id', $product_ids);
				})->where("order_id",">",0);
			} elseif ($filter['suppliers_type'] == 2) {
				$query->hasWhere("product",function ($query) use ($suppliers_id) {
					$query->where('suppliers_id', $suppliers_id);
				})->where("product_id",">",0);
			} else {
				$query->whereNotIn("msg_type",AdminMsg::SUPPLIERS_BY_PRODUCT_TYPE);
			}
		}

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('admin_msg', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['msg_type']) && !empty($filter['msg_type'])) {
            $query->where('msg_type', $filter['msg_type']);
        }

        if (isset($filter['is_read']) && $filter['is_read'] > -1) {
            $query->where('is_readed', $filter['is_read']);
        }

        // 店铺id
        if (isset($filter["shop_id"]) && $filter["shop_id"] > 0) {
            $query->where('shop_id', $filter["shop_id"]);
        }

        // 店铺id
        if (isset($filter["vendor_id"]) && $filter["vendor_id"] > 0) {
            $query->where('vendor_id', $filter["vendor_id"]);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return AdminMsg
     * @throws ApiException
     */
    public function getDetail(int $id): AdminMsg
    {
        $result = AdminMsg::where('msg_id', $id)->find();

        if (!$result) {
            throw new ApiException('管理员消息不存在');
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
        return AdminMsg::where('msg_id', $id)->value('title');
    }

    /**
     * 设置单个已读
     * @param $id
     * @return bool
     * @throws ApiException
     */
    public function setReaded($id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = AdminMsg::where('msg_id', $id)->save(['is_readed' => 1]);
        return $result !== false;
    }

    /**
     * 设置全部已读
     * @return bool
     */
    public function setAllReaded(int $shop_id = 0, int $vendor_id = 0): bool
    {

        $model=AdminMsg::where('admin_id', 0);
        if ($shop_id > 0) {
            $model->where('shop_id', $shop_id);
        }
        if ($vendor_id > 0) {
            $model->where('vendor_id', $vendor_id);
        }
        $result = $model->save(['is_readed' => 1]);
        return $result !== false;
    }

    /**
     * 获取消息类型
     * @return array
     */
    public function getMsgType(int $shop_id, int $vendor_id = 0): array
    {

        $msg_type_arr = [
            [
                'cat_id' => 1,
                'cat_name' => '交易消息',
                'child' => [
                    '11' => '新订单',
                    '12' => '已付款订单',
                    '13' => '订单完成',
                ],
            ],
            [
                'cat_id' => 2,
                'cat_name' => '商品消息',
                'child' => [
                    '21' => '商品库存预警',
                    '22' => '商品无货',
                    '23' => '商品下架',
					'24' => '商品审核通知'
                ],
            ],
            [
                'cat_id' => 3,
                'cat_name' => '售后服务',
                'child' => [
                    '31' => '订单取消',
                    '32' => '售后申请',
                    '33' => '提现申请',
                    '34' => '发票资质审核',
                    '35' => '发票申请',
                ],
            ],
            [
                'cat_id' => 4,
                'cat_name' => '店铺服务',
                'child' => [
                    '41' => '店铺入驻申请',
                    '42' => '店铺资质修改',
                    '43' => '店铺违规',
                ],
            ],
            [
                'cat_id' => 5,
                'cat_name' => '其它消息',
                'child' => [
                    '51' => '系统消息',
                    '52' => '待办任务',
                    '53' => '意见反馈',
                ],
            ],

        ];

        $arr = array();
        $unread_msg_type = AdminMsg::where('is_readed', 0)
            ->where('admin_id', 0)
            ->group('msg_type')
            ->field('msg_type, COUNT(*) AS count');

		if ($shop_id > 0) {
			$unread_msg_type = $unread_msg_type->where('shop_id', $shop_id);
		}

        if ($vendor_id > 0) {
            $unread_msg_type = $unread_msg_type->where('vendor_id', $vendor_id);
        }

		$unread_msg_type = $unread_msg_type->select();

        $unread_arr = array();
        foreach ($unread_msg_type as $key => $value) {
            $unread_arr[$value['msg_type']] = $value['count'];
        }
        foreach ($msg_type_arr as $key => $row) {
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['unread_count'] = 0;
            foreach ($row['child'] as $k => $value) {
                $k = intval($k);
                $arr[$key]['child'][$k]['name'] = $value;
                $arr[$key]['child'][$k]['msg_type'] = $k;
                $arr[$key]['child'][$k]['unread_count'] = 0;
                if (isset($unread_arr[$k]) && $unread_arr[$k] > 0) {
                    $arr[$key]['unread_count'] += $unread_arr[$k];
                    $arr[$key]['child'][$k]['unread_count'] = $unread_arr[$k];
                }
            }
        }
        return $arr;
    }

    /**
     *
     * @param $order_id
     * @return void
     */
    public function newOrderMessage($order_id)
    {
        AdminMsg::create([
            'type' => 11,
            'send_time' => time(),
            'title' => '',
            'content' => '',
            'order_id' => $order_id,
        ]);
    }

	/**
	 * 生成消息
	 * @param array $data
	 * @return bool
	 * @throws ApiException
	 */
	public function createMessage(array $data):bool
	{
		try {
			$msg = AdminMsg::create($data);
			return $msg->msg_id;
		} catch (\Exception $e) {

		}
		return true;
	}

}
