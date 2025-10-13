<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 优惠券
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\promotion\CouponService;
use app\service\admin\user\UserCouponService;
use think\App;
use think\Response;
use utils\Util;

/**
 * 我的优惠券控制器
 */
class Coupon extends IndexBaseController
{
    protected UserCouponService $userCouponService;
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app, UserCouponService $userCouponService)
    {
        parent::__construct($app);
        $this->userCouponService = $userCouponService;
    }

    /**
     * 会员优惠券列表
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'start_date',
            'sort_order' => 'asc',
        ], 'get');
        $filter['user_id'] = request()->userId;
        $filterResult = $this->userCouponService->getFilterResult($filter);

        // 格式化金额字段
        foreach ($filterResult["list"] as &$item) {
            if (isset($item['coupon_money'])) {
                $item['coupon_money'] = Util::formatAmount($item['coupon_money']);
            }
            if (isset($item['coupon_discount'])) {
                $item['coupon_discount'] = Util::formatAmount($item['coupon_discount']);
            }
        }
        return $this->success([
            'records' => $filterResult["list"],
            'total' => $filterResult["count"],
        ]);
    }

    /**
     * 删除优惠券
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function del(): Response
    {
        $id = $this->request->all('id/d', 0);
        $result = $this->userCouponService->deleteUserCoupon($id);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang('删除失败'));
    }

    /**
     * 优惠券列表
     * @return Response
     */
    public function getList(): Response
    {
        $filter = $this->request->only([
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'add_time',
            'sort_order' => 'desc',
            'shop_id' => -1
        ], 'get');
        $filter["is_show"] = 1;
        $filter["valid_date"] = 1;
        $filter["receive_date"] = 1;
        $filter["receive_flag"] = 1; // 根据领取状态排序
        $filterResult = app(CouponService::class)->getFilterResult($filter);
        // 格式化金额字段
        foreach ($filterResult as &$item) {
            if (isset($item['coupon_money'])) {
                $item['coupon_money'] = Util::formatAmount($item['coupon_money']);
            }
            if (isset($item['coupon_discount'])) {
                $item['coupon_discount'] = Util::formatAmount($item['coupon_discount']);
            }
        }
        $total = app(CouponService::class)->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 领取优惠券
     * @return Response
     * @throws \exceptions\ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function claim(): Response
    {
        $coupon_id = $this->request->all('coupon_id/d', 0);
        $result = app(CouponService::class)->claimCoupons($coupon_id, request()->userId);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang('领取失败'));
    }

    /**
     * 优惠券详情
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function detail(): Response
    {
        $id = $this->request->all('id/d', 0);
        $item = app(CouponService::class)->getDetail($id, request()->userId);
        return $this->success($item);
    }


}
