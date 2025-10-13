<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 会员留言
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\user;

use app\adminapi\AdminBaseController;
use app\service\admin\user\FeedbackService;
use think\App;

/**
 * 会员留言控制器
 */
class Feedback extends AdminBaseController
{
    protected FeedbackService $feedbackService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param FeedbackService $feedbackService
     */
    public function __construct(App $app, FeedbackService $feedbackService)
    {
        parent::__construct($app);
        $this->feedbackService = $feedbackService;
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
            'type' => -1,
			'status/d' => -1
        ], 'get');
		$filter['shop_id'] = $this->shopId;

        $filterResult = $this->feedbackService->getFilterResult($filter);
        $total = $this->feedbackService->getFilterCount($filter);

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
        $item = $this->feedbackService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 执行添加或操作
     *
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'title' => '',
            'parent_id/d' => 0,
            'email' => '',
            'content' => '',
            'mobile' => '',
            'feedback_pics' => [],
            'product_id/d' => 0,
            'order_id/d' => 0,
            "complaint_info" => '',
            'shop_id/d' => 0,
        ], 'post');

        $result = $this->feedbackService->updateFeedback($id, $data, true);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('会员留言更新失败');
        }
    }

    /**
     * 执行添加或更新操作
     *
     * @return \think\Response
     */
    public function update(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'title' => '',
            'parent_id/d' => 0,
            'email' => '',
            'content' => '',
            'mobile' => '',
            'feedback_pics' => [],
            'product_id/d' => 0,
            'order_id/d' => 0,
            "complaint_info" => '',
            'shop_id/d' => 0,
        ], 'post');

        $result = $this->feedbackService->updateFeedback($id, $data, false);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('会员留言更新失败');
        }
    }

    /**
     * 删除
     *
     * @return \think\Response
     */
    public function del(): \think\Response
    {
        $id =$this->request->all('id/d', 0);
        $this->feedbackService->deleteFeedback($id);
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
                $this->feedbackService->deleteFeedback($id);
            }
            return $this->success();
        } else {
            return $this->error('#type 错误');
        }
    }
}
