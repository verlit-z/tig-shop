<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 评论晒单                                 +
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\product\CommentService;
use think\App;
use think\Response;
use utils\Util;

/**
 * 评论晒单控制器
 */
class Comment extends IndexBaseController
{
    protected CommentService $commentService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param CommentService $commentService
     */
    public function __construct(App $app, CommentService $commentService)
    {
        parent::__construct($app);
        $this->commentService = $commentService;
    }

    /**
     * 评论数量
     * @return Response
     * @throws \think\db\exception\DbException
     */
    public function subNum(): Response
    {
        $item = $this->commentService->getSubNum(request()->userId);
        return $this->success($item);
    }

    /**
     * 晒单列表
     * @return Response
     */
    public function showedList(): Response
    {
        $filter = $this->request->only([
            'is_showed/d' => -1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'order_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->commentService->getShowPics($filter, request()->userId);
        return $this->success([
            'records' => $filterResult["list"],
            'total' => $filterResult["count"],
        ]);
    }

    /**
     * 已评价列表
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'comment_id',
            'sort_order' => 'desc',
            'user_id/d' => request()->userId,
        ], 'get');

        $filterResult = $this->commentService->getFilterResult($filter);
        $total = $this->commentService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 商品评价 / 晒单
     * @return Response
     */
    public function evaluate(): Response
    {
        $data = $this->request->only([
            "product_id/d" => 0,
            "order_id/d" => 0,
            "order_item_id/d" => 0,
            'comment_rank/d' => 1,
            'comment_tag/a' => [],
            'content' => '',
            'show_pics/a' => [],
            "shop_id/d" => 0,
        ], 'post');
        $result = $this->commentService->updateEvaluate($data, request()->userId);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang('评论失败'));
    }

    /**
     * 评价/晒单详情
     * @return Response
     * @throws \exceptions\ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(): Response
    {
        $id = $this->request->all('id/d', 0);
        $item = $this->commentService->getCommentDetail($id);
        return $this->success($item);
    }

}
