<?php
namespace app\model\merchant;

use app\model\authority\AdminRole;
use app\model\authority\AdminUser;
use app\model\user\User;
use think\Model;

class AdminUserShop extends Model
{
    protected $pk = 'id';
    protected $table = 'admin_user_shop';
    protected $json = ['auth_list'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;

    public function getAuthListAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value, true);
    }

    public function setAuthListAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    // 关联管理员
    public function adminUser()
    {
        return $this->hasOne(AdminUser::class, 'admin_id', 'admin_id')
                ->field(['admin_id',"mobile",'avatar','merchant_id','username']);
    }

    // 关联用户
    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')
                ->field(['user_id','username','avatar','mobile','nickname']);
    }

    //关联权限表
    public function role()
    {
        return $this->hasOne(AdminRole::class, 'role_id', 'role_id')
            ->field(['role_id','role_name','shop_id']);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id')
            ->field(['shop_id','shop_title','shop_logo','contact_mobile','status','add_time']);
    }


}
