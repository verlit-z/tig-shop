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

use app\model\user\User;
use think\Model;
use utils\Time;

class Merchant extends Model
{
    protected $pk = 'merchant_id';
    protected $table = 'merchant';

    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = 'int';
    protected $json = ['merchant_data', 'base_data', 'shop_data'];
    protected $jsonAssoc = true;
    protected $append = [
        'type_text',
        'status_text',
    ];


    public function getMerchantDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setMerchantDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getShopDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setShopDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getBaseDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setBaseDataAttr($value)
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

    const TYPE_LIST = [
        1 => '个人认证',
        2 => '企业认证'
    ];

    const STATUS_LIST = [
        1 => '正常',
        2 => '取消认证资格'
    ];

    public function getTypeTextAttr($value, $data): string
    {
        return self::TYPE_LIST[$data['type']] ?: '';
    }

    public function getStatusTextAttr($value, $data): string
    {
        return isset($data['status']) && self::STATUS_LIST[$data['status']] ? self::STATUS_LIST[$data['status']] : '';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->field(["user_id", 'username']);
    }

    public function admin()
    {
        return $this->hasOne(MerchantUser::class, 'merchant_id', 'merchant_id')->where('is_admin', 1);
    }

    public function shop()
    {
        return $this->hasMany(Shop::class, 'merchant_id', 'merchant_id');
    }


}
