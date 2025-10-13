<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 店铺
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\merchant;

use app\model\order\Order;
use app\model\product\Product;
use app\model\user\CollectShop;
use app\service\admin\order\OrderService;
use think\Model;
use utils\Time;

class Shop extends Model
{
    protected $pk = 'shop_id';
    protected $table = 'shop';

    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = 'int';
    protected $json = ['kefu_inlet'];
    protected $jsonAssoc = true;
    protected $append = [
        'status_text',
    ];

    public function getKefuInletAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setKefuInletAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
    // 字段处理
    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }

    const STATUS_LIST = [
        1 => '开业',
        4 => '暂停运营',
        10 => '关店'
    ];

    //暂停运营
    const STATUS_CLOSE = 4;
    //关店
    const STATUS_CLOSE_SHOP = 10;


    public function getStatusTextAttr($value, $data): string
    {
        if (isset($data['status'])) {
            return self::STATUS_LIST[$data['status']] ?: '';
        }
        return '';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'merchant_id', 'merchant_id');
    }

    public function hotProduct()
    {
        return $this->hasMany(Product::class, 'shop_id', 'shop_id')->where('is_hot', 1)->where('product_status',
            1)->where('is_delete', 0);
    }

    public function product()
    {
        return $this->hasMany(Product::class, 'shop_id', 'shop_id')->where('product_status',
            1)->where('is_delete', 0);
    }
    public function newProduct()
    {
        return $this->hasMany(Product::class, 'shop_id', 'shop_id')->where('is_new', 1)->where('product_status',
            1)->where('is_delete', 0);
    }

    public function bestProduct()
    {
        return $this->hasMany(Product::class, 'shop_id', 'shop_id')->where('is_best', 1)->where('product_status',
            1)->where('is_delete', 0);
    }

    public function adminUserShop()
    {
        return $this->hasOne(AdminUserShop::class, 'shop_id', 'shop_id');
    }

	// 收藏店铺
	public function collect()
	{
		return $this->hasMany(CollectShop::class, 'shop_id', 'shop_id');
	}

	// 最新上架商品前5个
	public function listingProduct()
	{
		return $this->hasMany(Product::class, 'shop_id', 'shop_id')
			->where('product_status', 1)
			->where("is_delete",0)
			->order("product_id",'desc')
			->limit(5);
	}

	// 上架商品总数
	public function listing()
	{
		return $this->hasMany(Product::class, 'shop_id', 'shop_id')->where('product_status', 1)->where("is_delete",0);
	}

    // 获取待结算金额
    public function getUnSettlementOrderAttr($value, $data)
    {
        if (isset($data['shop_id'])) {
            return app(OrderService::class)->getFilterSum([
                'shop_id' => $data['shop_id'],
                'order_status' => [Order::ORDER_CONFIRMED,Order::ORDER_PROCESSING,Order::ORDER_COMPLETED],
                'is_settlement' => 0
            ], 'paid_amount');
        }
        return '0.00';
    }

//    public function getKefuInletAttr($value,$data)
//    {
//        if (isset($data['kefu_inlet'])) {
//            return is_array($data['kefu_inlet']) ? $data['kefu_inlet'] : [];
//        }
//        return [];
//    }
}
