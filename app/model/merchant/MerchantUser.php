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

use app\model\authority\AdminUser;
use app\model\user\User;
use think\Model;
use utils\Time;

class MerchantUser extends Model
{
    protected $pk = 'merchant_user_id';
    protected $table = 'merchant_user';

    protected $autoWriteTimestamp = false;

    // 字段处理
    public function getAddTimeAttr($value): string
    {
        return Time::format($value);
    }

    public function user(): \think\model\relation\HasOne
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->field(["user_id", 'username']);
    }

    public function adminUser(): \think\model\relation\HasOne
    {
        return $this->hasOne(AdminUser::class, 'admin_id', 'admin_user_id')->field(["admin_id", 'username']);
    }

    public function adminUserBind(): \think\model\relation\HasOne
    {
        return $this->hasOne(AdminUser::class, 'admin_id', 'admin_user_id')->bind(["admin_id", 'username']);
    }


}
