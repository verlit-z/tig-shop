<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 订单
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\order;

use app\model\merchant\Shop;
use app\model\payment\PayLog;
use app\model\product\Comment;
use app\model\salesman\SalesmanCustomer;
use app\model\salesman\SalesmanOrder;
use app\model\user\User;
use think\Model;
use utils\Time;
use utils\Util;

class Order extends Model
{
    protected $pk = 'order_id';
    protected $table = 'order';
    protected $json = [
        'order_extension',
        'shipping_type',
        'region_ids',
        'region_names',
        "address_data",
        "invoice_data"
    ];
    protected $jsonAssoc = true;
    // 订单状态
    const ORDER_PENDING = 0; //待确认，待支付
    const ORDER_CONFIRMED = 1; //已确认，待发货（支付后同步为此状态）
    const ORDER_PROCESSING = 2; //处理中，已发货（发货后同步为此状态）
    const ORDER_CANCELLED = 3; //已取消
    const ORDER_INVALID = 4; //无效
    const ORDER_COMPLETED = 5; //已完成

    // 配送状态
    const SHIPPING_PENDING = 0; //待发货
    const SHIPPING_SENT = 1; //已发货
    const SHIPPING_SHIPPED = 2; //已收货
    const SHIPPING_FAILED = 3; //配送失败

    // 支付状态
    const PAYMENT_UNPAID = 0; //未支付
    const PAYMENT_PROCESSING = 1; //支付中
    const PAYMENT_PAID = 2; //已支付
    const PAYMENT_REFUNDING = 3; //退款中
    const PAYMENT_REFUNDED = 4; //已退款

    // 订单评价状态
    const COMMENT_PENDING = 0;
    const COMMENT_COMPLETED = 1;

    // 支付类型
    const PAY_TYPE_ID_ONLINE = 1;
    const PAY_TYPE_ID_CASH = 2;
    const PAY_TYPE_ID_OFFLINE = 3;

    // 订单类型
    const ORDER_TYPE_NORMAL = 1;
    const ORDER_TYPE_PIN = 2;
    const ORDER_TYPE_EXCHANGE = 3;
    const ORDER_TYPE_BARGAIN = 5;
    const ORDER_TYPE_VIRTUAL = 6;
    const ORDER_TYPE_PAID = 7;
    const ORDER_TYPE_CARD = 8;


    const ORDER_TYPE_MAP = [
        self::ORDER_TYPE_NORMAL => '普通商品订单',
        self::ORDER_TYPE_PIN => '拼团商品订单',
        self::ORDER_TYPE_EXCHANGE => '兑换商品订单',
        self::ORDER_TYPE_BARGAIN => '砍一砍商品订单',
        self::ORDER_TYPE_VIRTUAL => '虚拟商品订单',
        self::ORDER_TYPE_PAID => '付费商品订单',
        self::ORDER_TYPE_CARD => '卡密商品订单'
    ];

    // 限制权限的订单类型
    const ORDER_TYPE_LIMIT = [
       // self::ORDER_TYPE_VIRTUAL,    // 虚拟商品订单支持售后
        self::ORDER_TYPE_PAID,
        self::ORDER_TYPE_CARD
    ];

    public function base()
    {

    }

    // 订单状态映射
    public const ORDER_STATUS_MAP = [
        self::ORDER_PENDING => '待支付', //待确认，显示为待支付
        self::ORDER_CONFIRMED => '待发货', //已确认，显示为待发货
        self::ORDER_PROCESSING => '已发货', //处理中，显示为已发货
        self::ORDER_CANCELLED => '已取消',
        self::ORDER_INVALID => '无效',
        self::ORDER_COMPLETED => '已完成',
    ];

    // 配送状态映射
    public const SHIPPING_STATUS_MAP = [
        self::SHIPPING_PENDING => '待发货',
        self::SHIPPING_SENT => '已发货',
        self::SHIPPING_SHIPPED => '已收货',
        self::SHIPPING_FAILED => '配送失败',
    ];

    public function getOrderExtensionAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setOrderExtensionAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getAddressDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setAddressDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getInvoiceDataAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return camelCase($value);
    }

    public function setInvoiceDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getShippingTypeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setShippingTypeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    // 支付状态映射
    public const PAY_STATUS_MAP = [
        self::PAYMENT_UNPAID => '待支付',
        self::PAYMENT_PROCESSING => '支付中',
        self::PAYMENT_PAID => '已支付',
        self::PAYMENT_REFUNDING => '退款中',
        self::PAYMENT_REFUNDED => '已退款',
    ];

    // 待评价状态
    public const COMMENT_STATUS_MAP = [
        self::COMMENT_PENDING => '待评价',
        self::COMMENT_COMPLETED => '已评价',
    ];

    // 支付类型
    public const PAY_TYPE_MAP = [
        self::PAY_TYPE_ID_ONLINE => '在线支付',
        self::PAY_TYPE_ID_CASH => '货到付款',
        self::PAY_TYPE_ID_OFFLINE => '线下支付',
    ];

    public function hasPay()
    {
        return $this->pay_status == self::PAYMENT_PAID;
    }
    // 定义关联的订单商品
    public function items()
    {
        // Order模型有多个OrderItem
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id')->with(['product', 'productSku','e_card']);
    }
    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')
            ->field(['username','nickname','user_id','mobile']);
    }

    public function payLog()
    {
        return $this->hasOne(PayLog::class, 'order_id', 'order_id')
            ->where('pay_status', 1)
            ->where('pay_code' ,'<>', 'offline')
            ->field(['pay_sn', 'pay_code', 'transaction_id', 'order_id']);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id')->field([
            'shop_id',
            'shop_title',
            'kefu_inlet',
            'kefu_link',
            'kefu_link',
            'kefu_phone',
            'description'
        ]);
    }

    // 关联评论
    public function comment()
    {
        return $this->hasOne(Comment::class, "order_id", "order_id")->where("parent_id", 0);
    }

    //获取saleman_customer记录
    public function salemanCustomer()
    {
        return $this->hasOne(SalesmanCustomer::class, "user_id", "user_id");
    }

    // 字段处理
    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }
    public function getPayTimeAttr($value): string
    {
        return Time::format($value);
    }
    public function getShippingTimeAttr($value): string
    {
        return Time::format($value);
    }
    public function getReceivedTimeAttr($value): string
    {
        return Time::format($value);
    }
    // 订单状态名称
    public function getOrderStatusNameAttr($value, $data): string
    {

        $name = self::ORDER_STATUS_MAP[$data['order_status']] ?? '';
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code')) && $name) {
            $cache = Util::lang($name);
            if ($cache) {
                return $cache;
            } else {
                return $name;
            }
        } else {
            return $name;
        }
    }
    // 物流状态名称
    public function getShippingStatusNameAttr($value, $data): string
    {
        $name = self::SHIPPING_STATUS_MAP[$data['shipping_status']] ?? '';

        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code')) && $name) {
            $cache = Util::lang($name);
            if ($cache) {
                return $cache;
            } else {
                return $name;
            }
        } else {
            return $name;
        }
    }

    public function getShippingTypeNameAttr($value, $data)
    {
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    // 支付状态名称
    public function getPayStatusNameAttr($value, $data): string
    {
        $name = self::PAY_STATUS_MAP[$data['pay_status']] ?? '';
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code')) && $name) {
            $cache = Util::lang($name);
            if ($cache) {
                return $cache;
            } else {
                return $name;
            }
        } else {
            return $name;
        }
    }
    // 查询待确认订单
    public function scopePending($query)
    {
        $query->where('order_status', self::ORDER_PENDING);
    }
    // 查询已完成订单
    public function scopeCompleted($query)
    {
        $query->where('order_status', self::ORDER_COMPLETED);
    }
    // 查询有效订单（一般用于统计订单，包括已支付和手动被确认的订单）
    public function scopeValidOrder($query)
    {
        $query->whereIn('order_status', [self::ORDER_CONFIRMED, self::ORDER_PROCESSING, self::ORDER_COMPLETED]);
    }
    // 查询已支付订单
    public function scopePaid($query)
    {
        $query->where('pay_status', self::PAYMENT_PAID);
    }
    // 查询待支付订单
    public function scopeAwaitPay($query)
    {
        $query->where('order_status', self::ORDER_PENDING);
    }
    // 查询待发货订单
    public function scopeAwaitShip($query)
    {
        $query->where('order_status', self::ORDER_CONFIRMED);
    }
    // 查询待收货订单
    public function scopeAwaitReceived($query)
    {
        $query->where('order_status', self::ORDER_PROCESSING);
    }
    // 查询待评价订单 -- 订单已完成才可评价
    public function scopeAwaitComment($query)
    {
        $query->where('comment_status', self::COMMENT_PENDING)->where('order_status', self::ORDER_COMPLETED);
    }
    // 查询附带当前店铺ID
    public function scopeThisStore($query)
    {
        return $query->where('shop_id', request()->shopId);
    }

    public function scopeShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    // 查询店铺平台订单
    public function scopeStorePlatform($query)
    {
        if (request()->shopId > 0) {
            return $query->where('shop_id', request()->shopId);
        } else {
            return $query;
        }
    }

    // 下单时间检索
    public function scopeAddTime($query, $value)
    {
        if (!empty($value) && is_array($value)) {
            list($start_date, $end_date) = $value;
            $start_date = Time::toTime($start_date);
            $end_date = Time::toTime($end_date) + 86400;
            $value = [$start_date, $end_date];
            return $query->whereTime('add_time', 'between', $value);
        }
    }

    // 支付时间检索
    public function scopePayTime($query, $value)
    {
        if (!empty($value)) {
            $value = is_array($value) ? $value : explode(',', $value);
            list($start_date, $end_date) = $value;
            $start_date = Time::toTime($start_date);
            $end_date = Time::toTime($end_date) + 86400;
            $value = [$start_date, $end_date];
            return $query->whereTime('pay_time', 'between', $value);
        }
    }

    // 支付类型
    public function getPayTypeNameAttr($value, $data): string
    {
        $name = self::PAY_TYPE_MAP[$data['pay_type_id']] ?? '';
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code')) && $name) {
            $cache = Util::lang($name);
            if ($cache) {
                return $cache;
            } else {
                return $name;
            }
        } else {
            return $name;
        }


    }

    //收货人地址
    public function getUserAddressAttr($value, $data): string
    {
        $region_name = "";
        if (!empty($data["region_names"])) {
            $region_name = implode(" ", array_unique($data["region_names"]));
            if (!empty($data["address"])) {
                $region_name .= ' ' . $data["address"];
            }
        }
        return $region_name;
    }

    // 订单商品总重量
    public function getTotalProductWeightAttr($value, $data): float
    {
        if (isset($data['order_id'])) {
            $items = $this->items()
                ->visible(['order_id',"quantity",'product_weight'])
                ->select()->toArray();
            $total_product_weight = 0;
            foreach ($items as $item) {
                $total_product_weight += $item['quantity'] * $item['product_weight'];
            }
            return $total_product_weight;
        }
        return 0;
    }
}
