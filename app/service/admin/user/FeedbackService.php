<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 会员留言
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\user;

use app\model\authority\AdminUser;
use app\model\msg\AdminMsg;
use app\model\order\Order;
use app\model\user\Feedback;
use app\model\user\User;
use app\service\admin\msg\AdminMsgService;
use app\service\common\BaseService;
use app\validate\user\FeedbackValidate;
use exceptions\ApiException;
use log\AdminLog;
use utils\Util;

/**
 * 会员留言服务类
 */
class FeedbackService extends BaseService
{
    protected FeedbackValidate $feedbackValidate;

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
        $query = $this->filterQuery($filter)->with([
            'product' => function ($query) {
                $query->field('product_id,product_name');
            },
            "order_info" => function ($query) {
                $query->field('order_id,order_sn');
            },
            'shop' => function ($query) {
                $query->field('shop_id,shop_title');
            },
        ])->append(["status_name", "type_name"]);
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
        $query = Feedback::query();
        // 处理筛选条件
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('content', 'like', '%' . $filter['keyword'] . '%');
        }
        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        // 留言类型
        if (isset($filter['type']) && $filter["type"] != -1) {
			$filter["type"] = is_array($filter["type"]) ? $filter['type'] : explode(",", $filter["type"]);
            $query->whereIn('type', $filter["type"]);
        }

        if (isset($filter["user_id"]) && $filter["user_id"] > 0) {
            $query->where("user_id", $filter["user_id"]);
        }

        // 区分留言列表和订单咨询列表
        if (isset($filter["is_order"]) && $filter["is_order"] != -1) {
            $query->isOrder($filter["is_order"]);
        }

        if (isset($filter["order_id"]) && $filter["order_id"] > 0) {
            $query->where("order_id", $filter["order_id"]);
        }

        if (isset($filter["product_id"]) && $filter["product_id"] > 0) {
            $query->where("product_id", $filter["product_id"]);
        }

		if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
			$query->where("shop_id", $filter['shop_id']);
		}

		if (isset($filter['status']) && $filter['status'] > -1) {
			$query->where("status", $filter['status']);
		}
        $query->where("parent_id", 0);

        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function getDetail(int $id): array
    {
        $result = Feedback::with([
            'product' => function ($query) {
                $query->field('product_id,product_name');
            },
            "order_info" => function ($query) {
                $query->field('order_id,order_sn');
            },
            'shop' => function ($query) {
                $query->field('shop_id,shop_title');
            },
            'reply' => function ($query) {
                $query->field("id,parent_id,username,email,mobile,content,status,type");
            },
        ])->where('id', $id)->append(["status_name", "type_name"])->find();

        if (!$result) {
            throw new ApiException('会员留言不存在');
        }

        return $result->toArray();
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return Feedback::where('id', $id)->value('title');
    }

    /**
     * 执行会员留言添加或更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateFeedback(int $id, array $data, bool $isAdd = false)
    {
        validate(FeedbackValidate::class)->only(array_keys($data))->check($data);

        $username = AdminUser::find(request()->adminUid)->username;

        $arr_reply = [
            'user_id' => request()->adminUid,
            'username' => $username,
            'email' => $data["email"],
            "mobile" => $data["mobile"],
            "content" => $data["content"],
            'feedback_pics' => $data["feedback_pics"],
        ];

        // 判断是否为回复
        if (empty($data["parent_id"])) {
            // 未回复的则新增回复记录
            $arr_reply["parent_id"] = $id;
            $arr_reply["title"] = "reply";
            Feedback::create($arr_reply);
            // 同时修改原留言的回复状态
            Feedback::where("id", $id)->save(['status' => 1]);
        } else {
            // 已回复的覆盖处理
            Feedback::where("parent_id", $data["parent_id"])->save($arr_reply);
        }
        AdminLog::add('会员留言(ID:' . $id . ')回复：' . $data['content']);
        return true;
    }

    /**
     * 删除会员留言
     *
     * @param int $id
     * @return bool
     */
    public function deleteFeedback(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $result = Feedback::destroy($id);

        if ($result) {
            AdminLog::add('删除会员留言:' . $get_name);
        }

        return $result !== false;
    }

    /**
     * 留言/咨询 -- 订单咨询/留言列表
     * @param array $filter
     * @return Object
     */
    public function orderInquiryList(array $filter): Object
    {
		$query = $this->filterQuery($filter)->with([
				"reply" => function ($query) {
					$query->field("id,parent_id,username,content,add_time");
				},
				'order_info',
				'user'
			])
			->field("id,title,add_time,user_id,username,content,type,status,product_id,order_id")
			->append(["type_name"]);
		$list = $query->page($filter["page"], $filter["size"])->select();
		return $list;
    }

    /**
     * 提交留言
     * @param array $data
     * @return mixed
     */
    public function submitFeedback(array $data, int $user_id): mixed
    {
        $username = User::findOrEmpty($user_id)->username;
        if (!$username) {
            throw new ApiException(Util::lang('用户不存在'));
        }
        $data["user_id"] = $user_id;
        $data["username"] = $username;
		if (isset($data['order_id']) && !empty($data['order_id'])) {
			$order = Order::find($data['order_id']);
			$data['shop_id'] = $order->shop_id;
		}
        $result = Feedback::create($data);
        app(AdminMsgService::class)->createMessage([
            'msg_type' => AdminMsg::MSG_TYPE_FEEDBACK,
            'title' => '您有一个新的意见反馈',
            'content' => "用户" . $username . "提交了一个新的意见反馈",
            'related_data' => [
                'id' => $result->id,
            ]
        ]);
        return $result->id;
    }

    /**
     * 获取商品咨询量
     * @param int $product_id
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getProductFeedbackCount(int $product_id): int
    {
        return Feedback::where("product_id", $product_id)->count();
    }
}
