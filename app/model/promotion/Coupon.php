<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 优惠券
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\promotion;

use app\api\controller\common\Util;
use app\model\user\UserCoupon;
use think\Model;
use utils\Time;

class Coupon extends Model
{
    protected $pk = 'coupon_id';
    protected $table = 'coupon';
    protected $json = ['send_range_data', 'limit_user_rank'];
    protected $jsonAssoc = true;
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

    protected $append = ['promotion_desc'];
    // 使用范围类型
    const SEND_RANGE_ALL = 0;
//    const SEND_RANGE_CATEGORY = 1;
//    const SEND_RANGE_BRAND = 2;
    const SEND_RANGE_PRODUCT = 3;
    const SEND_RANGE_EXCLUDING_PRODUCT = 4;

    const SEND_RANGE_MAP = [
        self::SEND_RANGE_ALL => '全场通用',
//        self::SEND_RANGE_CATEGORY => '指定分类',
//        self::SEND_RANGE_BRAND => '指定品牌',
        self::SEND_RANGE_PRODUCT => '指定商品',
        self::SEND_RANGE_EXCLUDING_PRODUCT => '不包含指定商品',
    ];


    public function getPromotionDescAttr($value, $data)
    {
        if ($data['reduce_type'] == 1) {
            if ($data['coupon_type'] == 1) {
                return \utils\Util::lang("满%s元减%s", '',
                    [floatval($data['min_order_amount']), floatval($data['coupon_money'])]);
            } else {
                return \utils\Util::lang("满%s元打%s折", '',
                    [floatval($data['min_order_amount']), floatval($data['coupon_discount'])]);
            }

        } elseif ($data['reduce_type'] == 2) {
            if ($data['coupon_type'] == 1) {
                return \utils\Util::lang('立减%s', '', [floatval($data['coupon_money'])]);
            } else {
                return \utils\Util::lang('打%s折', '', [floatval($data['coupon_discount'])]);
            }
        }
        return '';
    }


    public function getCouponNameAttr($value, $data)
    {
        if (!$value) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = \utils\Util::lang($value, '', [], 10);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    public function getCouponDescAttr($value, $data)
    {
        if (!$value) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = \utils\Util::lang($value, '', [], 11);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    // 时间格式转换
    public function getSendStartDateAttr($value)
    {
        return Time::format($value);
    }

    public function getSendEndDateAttr($value)
    {
        return Time::format($value);
    }

    public function getUseStartDateAttr($value)
    {
        return Time::format($value);
    }

    public function getUseEndDateAttr($value)
    {
        return Time::format($value);
    }

    // 优惠券是否被当前用户领取
    public function getIsReceiveAttr($value, $data)
    {
        if (isset($data["coupon_id"])) {
            $time = Time::now();
            $where = [
                ['coupon_id', '=', $data["coupon_id"]],
                ['user_id', '=', request()->userId],
            ];
            if ($data['limit_num'] > 0 && $data['limit_num'] > UserCoupon::where($where)->count()) {
                return 1;
            }
        }
        return 0;
    }

    // 优惠券被当前用户领取数量
    public function getReceiveNumAttr($value, $data)
    {
        if (isset($data["coupon_id"])) {
            $time = Time::now();
            $where = [
                ['coupon_id', '=', $data["coupon_id"]],
                ['user_id', '=', request()->userId],
            ];
            return UserCoupon::where($where)->count();
        }
        return 0;
    }

    public function type()
    {
        return $this->morphOne(Promotion::class, 'type', 'type');
    }

    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }
}
