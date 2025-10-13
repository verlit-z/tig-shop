<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 优惠券
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\CouponService;
use app\service\admin\user\UserRankService;
use app\validate\promotion\CouponValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 优惠券控制器
 */
class Coupon extends AdminBaseController
{
    protected CouponService $couponService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param CouponService $couponService
     */
    public function __construct(App $app, CouponService $couponService)
    {
        parent::__construct($app);
        $this->couponService = $couponService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'coupon_id',
            'sort_order' => 'desc',
        ], 'get');
        $filter['shop_id'] = request()->shopId;

        $filterResult = $this->couponService->getFilterResult($filter);

        foreach ($filterResult as &$item) {
            $item['timeText'] = empty(trim($item['use_start_date'])) && empty(trim($item['use_end_date']))
                ? "长期有效"
                : trim($item['use_start_date']) . ' 至 ' . trim($item['use_end_date']);
        }
        unset($item); // 释放引用

        $total = $this->couponService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 配置型
     * @return Response
     */
    public function config(): Response
    {
        // 会员等级
        $rank_list = app(UserRankService::class)->getUserRankList();
        return $this->success(
           $rank_list
        );
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->couponService->getDetail($id);
        if ($item->shop_id != request()->shopId) {
            throw new ApiException(/** LANG */'优惠券不存在');
        }

        return $this->success(
          $item
        );
    }

    /**
     * 请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'coupon_name' => '',
            'coupon_desc' => '',
            'coupon_money' => '',
            'min_order_amount' => '',
            'send_range' => '',
            'send_range_data' => "",
            'is_global' => '',
            'is_new_user' => '',
            'is_show' => '',
            'coupon_discount' => '',
            'coupon_type' => '',
            'enabled_click_get' => '',
            'limit_user_rank' => '',
            "use_start_date" => "",
            "use_end_date" => "",
            'send_type/d' => 1,
            'delay_day' => 0,
            'use_day' => 0,
            'send_num' => 1,
            'coupon_unit' => 1,
            'max_order_amount' => '',
            'limit_num' => 0,
            'reduce_type' => 1
        ], 'post');

        $data['shop_id'] = request()->shopId;
        if ($data['coupon_type'] == 1) {
            unset($data['coupon_discount']);
        }
        return $data;
    }

    /**
     * 添加
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create()
    {
        $data = $this->requestData();

        try {
            validate(CouponValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->couponService->createCoupon($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'优惠券添加失败');
        }
    }

    /**
     * 执行更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('coupon_id/d', 0);

        $item = $this->couponService->getDetail($id);
        if ($item->shop_id != request()->shopId) {
            throw new ApiException(/** LANG */'优惠券不存在');
        }

        $data = $this->requestData();
        $data["coupon_id"] = $id;

        try {
            validate(CouponValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->couponService->updateCoupon($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'优惠券更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        $item = $this->couponService->getDetail($id);
        if ($item->shop_id != request()->shopId) {
            throw new ApiException(/** LANG */'优惠券不存在');
        }

        if (!in_array($field, ['coupon_name', 'is_show', 'coupon_money','coupon_discount', 'min_order_amount'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'coupon_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->couponService->updateCouponField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->couponService->getDetail($id);
        if (!$item) {
            throw new ApiException(/** LANG */'优惠券不存在');
        }
        $this->couponService->deleteCoupon($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return Response
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->couponService->deleteCoupon($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
