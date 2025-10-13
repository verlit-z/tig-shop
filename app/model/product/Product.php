<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 商品管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\product;

use app\model\common\TranslationsData;
use app\model\merchant\Shop;
use app\model\order\Cart;
use app\model\promotion\SeckillItem;
use app\service\admin\product\CategoryService;
use think\Model;
use utils\Time;
use utils\Util;

class Product extends Model
{
    protected $pk = 'product_id';
    protected $table = 'product';
    protected $json = ['product_related', 'product_service_ids', 'paid_content'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    // 商品类型映射
    const PRODUCT_TYPE_NORMAL = 1;
    const PRODUCT_TYPE_VIRTUAL = 2;
    const PRODUCT_TYPE_CARD = 3;
    const PRODUCT_TYPE_PAID = 4;

    const PRODUCT_TYPE_MAP = [
        self::PRODUCT_TYPE_NORMAL => '普通商品',
        self::PRODUCT_TYPE_VIRTUAL => '虚拟商品',
        self::PRODUCT_TYPE_CARD => '卡密商品',
        self::PRODUCT_TYPE_PAID => '付费内容'
    ];

    public function getPicUrlAttr($value, $data)
    {
        if (!$value) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value, '', [], 7);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    public function getProductNameAttr($value, $data)
    {
        if (!$value) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value, '', [], 2);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

	public function getProductBriefAttr($value)
	{
		if (!empty($value)) {
			if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
				$cache = Util::lang($value);
				if ($cache) {
					return $cache;
				}
			}
		}
		return $value;
	}


    // 关联品牌
    public function brand()
    {
        return $this->hasOne(Brand::class, 'brand_id', 'brand_id')
            ->where('status', Brand::AUDIT_PASS)
            ->where('is_show', 1)
            ->bind(["brand_name", "brand_logo", "first_word", "is_show"]);
    }

    // 关联商品分类
    public function category()
    {
        return $this->hasOne(Category::class, 'category_id', 'category_id')->bind(["category_name", "parent_id", "is_show"]);
    }

    public function seckillMinPrice()
    {
        // 获取当前时间
        $now = Time::now();
        // 使用hasOne关联定义
        return $this->hasOne(SeckillItem::class, 'product_id', 'product_id')->where([['seckill_start_time', '<', $now], ['seckill_end_time', '>', $now]])->bind(['seckill_price'])->order('seckill_price', 'asc');
    }

    // 关联商品属性规格
    public function productSku()
    {
        return $this->hasMany(ProductSku::class, 'product_id', 'product_id');
    }

    public function cart()
    {
        return $this->hasMany(Cart::class, 'product_id', 'product_id');
    }

    public function pics()
    {
        return $this->hasMany(ProductGallery::class, 'product_id', 'product_id')->order('sort_order ASC');
    }

    public function skuMinPrice()
    {
        return $this->hasOne(ProductSku::class, 'product_id', 'product_id')->bind(['sku_price'])->order('sku_price', 'asc');
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
    }

    public function salesmanProduct()
    {
        return $this->hasOne(\app\model\salesman\SalesmanProduct::class, 'product_id', 'product_id');
    }

    public function eCardGroup()
    {
        return $this->hasOne(ECardGroup::class, 'group_id', 'card_group_id')->field(['group_id','group_name','shop_id','is_use']);
    }

    // 关联翻译
    public function transactionData()
    {
        return $this->hasOne(TranslationsData::class, 'data_id', 'product_id')->where('data_type',2);
    }

    // 审核状态
    const CHECK_STATUS_PENDING = 0; // 待审核
    const CHECK_STATUS_APPROVED = 1; // 审核通过
    const CHECK_STATUS_REJECTED = 2; // 审核未通过
    protected const CHECK_STATUS_MAP = [
        self::CHECK_STATUS_PENDING => '待审核',
        self::CHECK_STATUS_APPROVED => '审核通过',
        self::CHECK_STATUS_REJECTED => '审核未通过',
    ];

    // 推荐类型
    public function scopeIntroType($query, $value)
    {
        if (in_array($value, ['new', 'hot', 'best'])) {
            $value = 'is_' . $value;
        }
        if (!in_array($value, ['isNew', 'isHot', 'isBest'])) {
            return $query;
        }
        $value = convertCamelCase($value);
        return $query->where($value, 1);
    }

    // 获取分类名称
    public function getCategoryTreeNameAttr($value, $data)
    {
        $category_name = app(CategoryService::class)->getParentCategory($data['category_id'])["category_name"];
        return implode('|', $category_name);

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

    // 促销时间格式转换
    public function getPromoteStartDateAttr($value)
    {
        return Time::format($value);
    }

    public function getPromoteEndDateAttr($value)
    {
        return Time::format($value);
    }

}
