<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 会员等级
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\user;

use think\Model;
use utils\Util;

class UserRank extends Model
{
    protected $pk = 'rank_id';
    protected $table = 'user_rank';
    protected $json = ['rights'];
    protected $jsonAssoc = true;


    public function getRightsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setRightsAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    //等级类型名称
    const RANK_TYPE_SPECIAL = 1;
    const RANK_TYPE_NORMAL = 2;

    const RANK_TYPE_NAME = [
        self::RANK_TYPE_SPECIAL => '根据成长值',
        self::RANK_TYPE_NORMAL => '根据消费行为',
    ];

    //等级类型名称
    public function getRankTypeNameAttr($value, $data)
    {
        return isset($data['rank_type']) && $data['rank_type'] > 0 ? self::RANK_TYPE_NAME[$data['rank_type']] : "";
    }

	public function getRankNameAttr($value)
	{
		if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
			$cache = Util::lang($value);
			if ($cache) {
				return $cache;
			}
		}
		return $value;
	}

    // 会员权益
    public function getUserRightsAttr($value,$data)
    {
        $user_rights = [];
        // 拼接会员权益
        if (!empty($data['free_shipping'])) {
            array_push($user_rights,"会员包邮");
        }
        if (!empty($data['discount']) && $data['discount'] != "0.0") {
            array_push($user_rights,"会员折扣" . $data['discount'] . "折");
        }
        if (!empty($data['rank_point'])) {
            array_push($user_rights,$data['rank_point'] . "倍积分回馈");
        }
        if (!empty($user_rights)) {
            $user_rights = "享受" . implode("",$user_rights);
        } else {
            $user_rights = "";
        }
        return $user_rights;
    }

    // 会员数
    public function getUserCountAttr($value,$data)
    {
        $count = 0;
        if (!empty($data['rank_id'])) {
            $count = User::where('rank_id',$data['rank_id'])->count();
        }
        return $count;
    }

}
