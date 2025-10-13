<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 评论晒单
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\product;

use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\user\User;
use think\Model;

class Comment extends Model
{
    protected $pk = 'comment_id';
    protected $table = 'comment';
    protected $json = ['comment_tag', 'show_pics'];
    protected $jsonAssoc = true;
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

    public function getShowPicsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setShowPicsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    // 关联回复评论
    public function reply()
    {
        return $this->hasOne(Comment::class, 'parent_id', 'comment_id');
    }

    // 关联商品
    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id')->bind(['product_name', 'pic_thumb','product_sn']);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->bind(['nickname']);
    }
    // 关联订单
    public function orderInfo()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    // 待回复的订单
    public function scopeAwaitComment($query)
    {
        return $query->where("order_id", ">", 0)->where(["status" => 0, "parent_id" => 0]);
    }

    // 查询附带当前店铺ID
    public function scopeThisStore($query)
    {
        $query->where('shop_id', request()->shopId);
    }

    // 查询平台
    public function scopeStorePlatform($query)
    {
        if (request()->shopId > 0) {
            return $query->where('shop_id', request()->shopId);
        } else {
            return $query;
        }
    }

}
