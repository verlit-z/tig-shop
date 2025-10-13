<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 优惠券
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\promotion;

use app\model\promotion\Coupon;
use app\model\promotion\Promotion;
use app\model\user\UserCoupon;
use app\service\admin\user\UserCouponService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;
use utils\Util;

/**
 * 优惠券服务类
 */
class CouponService extends BaseService
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
        $filter['page'] = $filter['page'] ?? 1;
        $query = $this->filterQuery($filter);
        if (isset($filter["receive_flag"]) && $filter["receive_flag"] == 1) {
            $list = $query->append(["is_receive", "receive_num"])->select()->toArray();
            // 根据领取状态排序
            array_multisort(array_column($list, 'is_receive'), SORT_ASC, $list);
            $result = array_slice($list, (($filter["page"] ?? 1) - 1) * ($filter["size"] ?? 15), ($filter["size"] ?? 15));
        } else {
            $result = $query->order('coupon_id', 'desc')->page($filter['page'], $filter['size'])->append([
                "is_receive",
                "receive_num"
            ])->select()->toArray();
        }

        return $result;
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
        $query = Coupon::query();
        // 处理筛选条件
        $query->where('is_delete', 0);
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('coupon_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        if (isset($filter['is_show']) && $filter['is_show'] != -1) {
            $query->where('is_show', $filter['is_show']);
        }
        // 有效限内
        if (isset($filter['valid_date']) && $filter['valid_date'] === 1) {
            $time = Time::now();
            $query->where(function ($query) use ($time) {
                $query->where(function ($query) use ($time) {
                    $query->where('use_start_date', '<=', $time);
                    $query->where('use_end_date', '>=', $time);
                })->whereOr('send_type', '=', 0);
            });
        }
        //shop_id
        if (isset($filter["shop_id"]) && $filter['shop_id'] > -1) {
            $query->where('shop_id', '=', $filter["shop_id"]);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return Coupon | null
     */
    public function getDetail(int $id, int $user_id = 0): Coupon|null
    {
        $result = Coupon::where('coupon_id', $id)->append(['receive_num'])->find();
        if ($result) {
            if ($user_id > 0) {
                $result['is_receive'] = app(UserCouponService::class)->checkUserHasCoupon($id, $user_id) ? 1 : 0;
            } else {
                $result['is_receive'] = 0;
            }
        }
        return $result;
    }

    /**
     * 数据判断
     * @param array $data
     * @return array
     */
    public function dataJudge(array $data): array
    {
        if (!empty($data['use_start_date']) && !empty($data['use_end_date'])) {
            // 使用日期
            $data['use_start_date'] = Time::toTime($data['use_start_date']);
            $data['use_end_date'] = Time::toTime($data['use_end_date']);
        }
        if (empty($data['send_type'])) {
            $data['use_start_date'] = 0;
            $data['use_end_date'] = 0;
        } else {
            $data['delay_day'] = 0;
            $data['use_day'] = 0;
        }

        if (isset($data['reduce_type']) && !empty($data['reduce_type'])) {
            if ($data['reduce_type'] == 2) {
                // 无门槛时
                $data['min_order_amount'] = 0;
            }
        }
        return $data;
    }

    /**
     * 添加优惠券
     * @param array $data
     * @return bool
     */
    public function createCoupon(array $data): bool
    {
        $data = $this->dataJudge($data);
        $result = Coupon::create($data);
        Promotion::create([
            'type' => Promotion::TYPE_COUPON,
            'shop_id' => $data['shop_id'],
            'start_time' => $data['send_type'] > 0 ? $data['use_start_date'] : 0,
            'end_time' => $data['send_type'] > 0 ? $data['use_end_date'] : 0,
            'promotion_name' => $data['coupon_name'],
            'relation_id' => $result->coupon_id,
            'range' => $data['send_range'],
            'range_data' => $data['send_range_data'],
            'is_available' => $data['is_show'],
        ]);
        return $result !== false;
    }

    /**
     * 执行优惠券更新
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateCoupon(int $id, array $data): bool
    {
        $data = $this->dataJudge($data);
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = Coupon::where('coupon_id', $id)->save($data);
        Promotion::where([
            'type' => Promotion::TYPE_COUPON,
            'relation_id' => $id
        ])->update([
            'shop_id' => $data['shop_id'],
            'start_time' => $data['send_type'] > 0 ? $data['use_start_date'] : 0,
            'end_time' => $data['send_type'] > 0 ? $data['use_end_date'] : 0,
            'promotion_name' => $data['coupon_name'],
            'is_available' => $data['is_show'],
            'range' => $data['send_range'],
            'range_data' => $data['send_range_data'],
        ]);
        return $result !== false;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateCouponField(int $id, array $data)
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }

        $coupon = Coupon::find($id);
        if (empty($coupon)) {
            throw new ApiException(/** LANG */ '该优惠券不存在');
        }
        if (array_key_exists('min_order_amount', $data)) {
            if ($coupon->reduce_type == 2) {
                throw new ApiException(/** LANG */ '无门槛时，不能修改订单金额限制');
            }
        }

        if (array_key_exists('is_show', $data)) {
            // 同步 promotion 表的状态
            Promotion::where([
                'type' => Promotion::TYPE_COUPON,
                'relation_id' => $id
            ])->update(['is_available' => $data['is_show']]);
        }

        $result = $coupon->save($data);
        return $result !== false;
    }

    /**
     * 删除优惠券
     *
     * @param int $id
     * @return bool
     */
    public function deleteCoupon(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = Coupon::where('coupon_id', $id)->save(['is_delete' => 1]);
        Promotion::where([
            'type' => Promotion::TYPE_COUPON,
            'relation_id' => $id
        ])->save(['is_delete' => 1]);
        return $result !== false;
    }

    /**
     * PC 领取优惠券
     * @param int $coupon_id
     * @param int $user_id
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function claimCoupons(int $coupon_id, int $user_id): bool
    {
        $coupon = Coupon::find($coupon_id);
        if (empty($coupon)) {
            throw new ApiException(/** LANG */ Util::lang('优惠券不存在'));
        }
        $user_coupon = UserCoupon::where(['coupon_id' => $coupon_id, 'user_id' => $user_id])->count();
        // 领取数量限制
        if ($user_coupon >= $coupon->limit_num && $coupon->limit_num > 0) {
            throw new ApiException(/** LANG */ Util::lang('已超出领取数量限制'));
        }

        // 发放总量
        if (UserCoupon::where('coupon_id', $coupon_id)->count() >= $coupon->send_num && $coupon->send_num > 0) {
            throw new ApiException(/** LANG */ Util::lang('已超出发放数量限制'));
        }

        // 判断优惠券的使用时间
        if ($coupon->send_type) {
            // 固定时间
            $start_date = Time::toTime($coupon->use_start_date);
            $end_date = Time::toTime($coupon->use_end_date);
        } else {
            // 领取天数
            $start_date = strtotime('+' . $coupon->delay_day . 'days', Time::now());
            $end_date = strtotime('+' . $coupon->use_day . 'days', $start_date);
        }
        //会员是否能够领取
        if ($coupon->is_new_user == 1) {
            //新人专享
            if ($user_coupon > 0) {
                throw new ApiException(/** LANG */ Util::lang('该优惠券仅能领取一次'));
            }
            if (!app(UserService::class)->isNew($user_id)) {
                throw new ApiException(/** LANG */ Util::lang('该优惠券仅限新人领取'));
            }
        }
        if ($coupon->is_new_user == 2) {
            //会员专享
            $userRank = app(UserService::class)->getUserRankId($user_id);
            if (!in_array($userRank, $coupon->limit_user_rank)) {
                throw new ApiException(/** LANG */ Util::lang('您的会员等级无法领取该优惠券'));
            }
        }

        $coupon_data = [
            "coupon_id" => $coupon_id,
            "user_id" => $user_id,
            "start_date" => $start_date,
            "end_date" => $end_date,
        ];
        $result = UserCoupon::create($coupon_data);
        return $result !== false;
    }


}
