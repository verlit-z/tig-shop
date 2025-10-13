<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 管理员消息
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\msg;

use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\product\Product;
use think\Model;
use utils\Time;

class AdminMsg extends Model
{
    protected $pk = 'msg_id';
    protected $table = 'admin_msg';
	protected $createTime = "send_time";
	protected $autoWriteTimestamp = true;
    protected $json = ['related_data'];
	protected $jsonAssoc = true;

	// 消息类型
	// 交易消息
	const MSG_TYPE_ORDER_NEW = 11;
	const MSG_TYPE_ORDER_PAY = 12;
	const MSG_TYPE_ORDER_FINISH = 13;

	// 商品消息
	const MSG_TYPE_PRODUCT_LOW_STOCK = 21;
	const MSG_TYPE_PRODUCT_NO_STOCK = 22;
	const MSG_TYPE_PRODUCT_OFF_SHELF = 23;
	const MSG_TYPE_PRODUCT_AUDIT = 24;

	// 售后服务
	const MSG_TYPE_ORDER_CANCEL = 31;
	const MSG_TYPE_AFTERSALE_APPLY = 32;
	const MSG_TYPE_WITHDRAW_APPLY = 33;
	const MSG_TYPE_INVOICE_QUALIFICATION = 34;
	const MSG_TYPE_INVOICE_APPLY = 35;

	// 店铺服务
	const MSG_TYPE_SHOP_APPLY = 41;
	const MSG_TYPE_SHOP_MODIFY = 42;
	const MSG_TYPE_SHOP_VIOLATION = 43;

	// 其它消息
	const MSG_TYPE_SYSTEM = 51;
	const MSG_TYPE_TODO = 52;
	const MSG_TYPE_FEEDBACK = 53;
    const MSG_TYPE_QUICK_DELIVERY = 54;

	const MSG_TYPE_MAP = [
		self::MSG_TYPE_ORDER_NEW => '新订单',
		self::MSG_TYPE_ORDER_PAY => '已付款订单',
		self::MSG_TYPE_ORDER_FINISH => '订单完成',
		self::MSG_TYPE_PRODUCT_LOW_STOCK => '商品库存预警',
		self::MSG_TYPE_PRODUCT_NO_STOCK => '商品无货',
		self::MSG_TYPE_PRODUCT_OFF_SHELF => '商品下架',
		self::MSG_TYPE_PRODUCT_AUDIT => '商品审核通知',
		self::MSG_TYPE_ORDER_CANCEL => '订单取消',
		self::MSG_TYPE_AFTERSALE_APPLY => '售后申请',
		self::MSG_TYPE_WITHDRAW_APPLY => '提现申请',
		self::MSG_TYPE_INVOICE_QUALIFICATION => '发票资质审核',
		self::MSG_TYPE_INVOICE_APPLY => '发票申请',
		self::MSG_TYPE_SHOP_APPLY => '店铺入驻申请',
		self::MSG_TYPE_SHOP_MODIFY => '店铺资质修改',
		self::MSG_TYPE_SHOP_VIOLATION => '店铺违规',
		self::MSG_TYPE_SYSTEM => '系统消息',
		self::MSG_TYPE_TODO => '待办任务',
		self::MSG_TYPE_FEEDBACK => '意见反馈',
        self::MSG_TYPE_QUICK_DELIVERY => '发货提醒',
	];

	// 与供应商相关的商品信息类型
	const SUPPLIERS_BY_PRODUCT_TYPE = [
		self::MSG_TYPE_ORDER_NEW ,
		self::MSG_TYPE_ORDER_PAY,
		self::MSG_TYPE_ORDER_FINISH,
		self::MSG_TYPE_PRODUCT_LOW_STOCK,
		self::MSG_TYPE_PRODUCT_NO_STOCK,
		self::MSG_TYPE_PRODUCT_OFF_SHELF,
		self::MSG_TYPE_PRODUCT_AUDIT,
		self::MSG_TYPE_ORDER_CANCEL,
	];

	// 订单相关信息
	const ORDER_RELATED_TYPE = [
		self::MSG_TYPE_ORDER_NEW,
		self::MSG_TYPE_ORDER_PAY,
		self::MSG_TYPE_ORDER_FINISH,
		self::MSG_TYPE_ORDER_CANCEL,
	];

	// 商品相关信息
	const PRODUCT_RELATED_TYPE = [
		self::MSG_TYPE_PRODUCT_LOW_STOCK,
		self::MSG_TYPE_PRODUCT_NO_STOCK,
		self::MSG_TYPE_PRODUCT_OFF_SHELF,
		self::MSG_TYPE_PRODUCT_AUDIT,
	];

    public function getSendTimeAttr($value): string
    {
        return Time::format($value);
    }


    public function getRelatedDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setRelatedDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function items()
    {
        // Order模型有多个OrderItem
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

	public function product()
	{
		return $this->hasOne(Product::class, 'product_id', 'product_id');
	}

    public function getOrderAttr($value)
    {
        return $value ? $value : [];
    }
}
