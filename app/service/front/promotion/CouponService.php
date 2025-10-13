<?php

namespace app\service\front\promotion;

use app\model\promotion\Coupon;
use app\model\user\User;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use utils\Time;

/**
 * 优惠券服务
 */
class CouponService extends BaseService
{

    /**
     * 商品下的可领取的优惠券
     * @param object $product
     * @param int $user_id
     * @param int $shop_id
     * @return array
     */
    public function getAvailableCouponList(object $product, int $user_id = 0, int $shop_id = 0): array
    {
        if (empty($product)) return [];
        // 所有的优惠券
        $coupon = Coupon::where(["is_show" => 1, "is_delete" => 0, 'shop_id' => $shop_id])->select()->toArray();
        if (empty($coupon)) return [];

        $user = $user_id > 0 ? User::findOrEmpty($user_id) : null;

        $now = Time::now();
        foreach ($product as $item) {
            foreach ($coupon as $key => $value) {
                // 活动范围
                switch ($value['send_range']) {
                    case 0:
                        // 全部商品
                        break;
                    case 3:
                        // 指定商品
                        if (!in_array($item['product_id'], $value['send_range_data'])) {
                            unset($coupon[$key]);
                        }

                        break;
                    case 4:
                        // 不含商品
                        if (in_array($item['product_id'], $value['send_range_data'])) {
                            unset($coupon[$key]);
                        }
                        break;
                }

                // 优惠券使用方式
                if ($value['send_type']) {
                    // 固定时间 -- 当前时间不在范围内
                    if (!empty($value['use_start_date']) && !empty($value['use_end_date'])) {
                        if ($now < Time::toTime($value['use_start_date']) || $now > Time::toTime($value['use_end_date'])) {
                            unset($coupon[$key]);
                        }
                    }
                }

                // 是否新人专享
                if ($value['is_new_user'] == 1 && $user_id) {
                    // 已登录 -- 判断当前用户是否为新人
                    if (!app(UserService::class)->isNew($user_id)) {
                        unset($coupon[$key]);
                    }
                }

                // 会员等级
                if ($value['is_new_user'] == 2 && !empty($value['limit_user_rank']) && $user_id) {
                    if (!empty($user) && !in_array($user['rank_id'], $value['limit_user_rank'])) {
                        unset($coupon[$key]);
                    }
                }

            }
            $item->coupon_list = $coupon;
        }
        return $product->toArray();
    }

    /**
     * 获得优惠券的描述
     * @param $products
     * @param $coupon
     * @return mixed
     */
    public function getCouponDesc($products, $coupon)
    {
        if (($coupon['coupon_unit'] == 1 && $coupon['min_order_amount'] <= $products['total_subtotal']) || ($coupon['coupon_unit'] == 2 && $coupon['min_order_amount'] <= $products['total_quantity'])) {

            if ($coupon['reduce_type'] == 1) {
                $unit = $coupon['coupon_unit'] == 1 ? '元' : '件';
                if ($coupon['coupon_type'] == 2) {
                    $coupon['cart_desc'] = \utils\Util::lang('已满%s' . $unit . '，已打%s折', '',
                        [$coupon['min_order_amount'], $coupon['coupon_discount']]);
                } else {
                    $coupon['cart_desc'] = \utils\Util::lang('已满%s' . $unit . '，已减%s元', '',
                        [$coupon['min_order_amount'], $coupon['amount']]);
                }

            } else {
                $coupon['cart_desc'] = \utils\Util::lang('立减', '', [$coupon['amount']]);
            }
            return $coupon;
        } else {
            if ($coupon['reduce_type'] == 1) {
                if ($coupon['coupon_unit'] == 1) {
                    if ($coupon['coupon_type'] == 2) {
                        $coupon['cart_desc'] = \utils\Util::lang('再买%s元，可打%s折', '',
                            [($coupon['min_order_amount'] - $products['total_subtotal']), $coupon['coupon_discount']]);
                    } else {
                        $coupon['cart_desc'] = \utils\Util::lang('再买%s元，可减%s元', '',
                            [$coupon['min_order_amount'], $coupon['amount']]);
                    }

                } else {
                    if ($coupon['coupon_type'] == 2) {
                        $coupon['cart_desc'] = \utils\Util::lang('再买%s件，可打%s折', '',
                            [($coupon['min_order_amount'] - $products['total_quantity']), $coupon['coupon_discount']]);
                    } else {
                        $coupon['cart_desc'] = \utils\Util::lang('再买%s件，可减%s元', '',
                            [($coupon['min_order_amount'] - $products['total_quantity']), $coupon['coupon_discount']]);
                    }
                }

            } else {
                $coupon['cart_desc'] = \utils\Util::lang('立减', '', [$coupon['amount']]);
            }
        }
        return $coupon;
    }

    /**
     * 批量商品重新按活动计算价格
     * @param array $products
     * @return array
     */
    public function parsePrice(array $products, mixed $coupon): array
    {
        $result = [];
        if (!$coupon['is_receive']) {//未领取返回
            return $result;
        }
        //看是否满足优惠券要求
        if (($coupon['coupon_unit'] == 1 && ($coupon['min_order_amount'] <= $products['total_subtotal'] || $coupon['reduce_type'] == 2)) || ($coupon['coupon_unit'] == 2 && $coupon['min_order_amount'] <= $products['total_quantity'])) {
            //统一按优惠比例
            $discount = 0;
            $coupon['amount'] = 0;
            $priceAmount = 0;
            foreach ($products['list'] as $product) {
                $priceAmount = bcadd($priceAmount, $product['price'] * $product['quantity'], 2);
            }
            if ($coupon['coupon_type'] == 1) {
                if ($priceAmount - $coupon['coupon_money'] <= 0) {
                    $discount = 0;
                    $coupon['amount'] = $coupon['coupon_money'];
                } else {
                    $coupon['amount'] = $coupon['coupon_money'];
                    $discount = ($priceAmount - $coupon['coupon_money']) / $priceAmount;
                }
            } else {
                //预估的优惠折扣金额
                $check_discount_amount = $products['total_subtotal'] * (1 - $coupon['coupon_discount'] / 10);
                if ($coupon['max_order_amount'] > 0 && $check_discount_amount > $coupon['max_order_amount']) {
                    $check_discount_amount = $coupon['max_order_amount'];
                }
                if ($priceAmount - $check_discount_amount <= 0) {
                    $discount = 0;
                } else {
                    $discount = ($priceAmount - $check_discount_amount) / $priceAmount;
                }
                //$coupon['amount'] = $check_discount_amount * $product['quantity'];
            }

            foreach ($products['list'] as $product) {
                if ($coupon['coupon_type'] == 1) {
                    $result[$product['cart_id']]['price'] = $discount * $product['price'];
                } else {
                    //折扣后金额
                    $discountAfterPrice = $product['price'] * $coupon['coupon_discount'] / 10;

                    $result[$product['cart_id']]['price'] = $discountAfterPrice;


                    $coupon['amount'] = $coupon['amount'] + bcadd(0,
                            $product['price'] - $result[$product['cart_id']]['price'], 2) * $product['quantity'];
                }
            }
            return ['carts' => $result, 'promotion' => $coupon];
        }
        return [];

    }

    /**
     * 获得商品优惠券
     * @return Coupon|array
     */
    public function getProductCouponList(
        int $product_id,
        int $shop_id,
        int $user_id,
    ): Coupon|array
    {
        $coupon = Coupon::query();
        $coupon->where('shop_id', $shop_id);
        $coupon->where('is_show', 1);
        $coupon->where('is_delete', 0);
        //排除已过期
        $time = Time::now();
        $coupon->where(function ($query) use ($time) {
            $query->where(function ($query) use ($time) {
                $query->where('use_start_date', '<=', $time);
                $query->where('use_end_date', '>=', $time);
            })->whereOr('send_type', '=', 0);
        });
        $coupon = $coupon->append(['receive_num'])->select()->toArray();
        foreach ($coupon as $key => $c) {
            $send_range_data = $c['send_range_data'];
            //不为全部商品时判断
            if ($c['send_range'] != 0) {
                if ($c['send_range'] == 3) {
                    if (is_array($send_range_data) && !in_array($product_id, $send_range_data)) {
                        unset($coupon[$key]);
                    }
                    //不包含指定商品
                } elseif ($c['send_range'] == 4) {
                    if (is_array($send_range_data) && in_array($product_id, $send_range_data)) {
                        unset($coupon[$key]);
                    }
                }
            }
//            //新人专享优惠券
//            if ($c['is_new_user'] && $user_id) {
//                if (!app(UserService::class)->isNew($user_id)) {
//                    unset($coupon[$key]);
//                }
//            }
//            //仅限会员等级
//            if ($c['limit_user_rank'] && $user_id) {
//                $limit_user_rank = $c['limit_user_rank'];
//                if (!empty($limit_user_rank) && is_array($limit_user_rank)) {
//                    $user = User::find($user_id);
//                    if (!in_array($user['rank_id'], $limit_user_rank)) {
//                        unset($coupon[$key]);
//                    }
//                }
//            }
        }
        return array_values($coupon);

    }

}