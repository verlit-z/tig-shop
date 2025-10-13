<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 商品规格
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\shipping;

use app\model\merchant\Shop;
use think\Model;

class ShippingTpl extends Model
{
    protected $pk = 'shipping_tpl_id';
    protected $table = 'shipping_tpl';

	public function shop()
	{
		return $this->hasOne(Shop::class, 'shop_id', 'shop_id')
			->field(["shop_id","shop_title","status"]);
	}
}
