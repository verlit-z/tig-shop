<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 积分日志
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\user;

use think\Model;
use utils\Time;
use utils\Util;

class UserPointsLog extends Model
{
    protected $pk = 'log_id';
    protected $table = 'user_points_log';
    protected $createTime = 'change_time';
    protected $autoWriteTimestamp = true;

    // 关联用户
    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->bind(["username"]);
    }

    //操作时间
    public function getChangeTimeAttr($value)
    {
        return Time::format($value);
    }

    // 积分变化名称
    const POINTS_INCREASE = 1;
    const POINTS_DECREASE = 2;

    public static $typeName = [
        self::POINTS_INCREASE => '增加',
        self::POINTS_DECREASE => '减少',
    ];

    public static function getChangeTypeNameAttr($value, $data)
    {
        return Util::lang(self::$typeName[$data["change_type"]]) ?? '';
    }

    // 根据用户检索
    public function scopeUserName($query, $value)
    {
        return $query->hasWhere('user', function ($query) use ($value) {
            $query->where('username', 'like', '%' . $value . '%');
        });
    }


    public function getChangeDescAttr($value, $data)
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
}
