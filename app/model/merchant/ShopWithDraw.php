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

class ShopWithDraw extends Model
{
    protected $pk = 'shop_withdraw_log_id';
    protected $table = 'shop_withdraw';

    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = 'int';

    protected $json = ['account_data'];
    protected $jsonAssoc = true;
    protected $append = ['status_text'];

    public function getAccountDataAttr($value)
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

    const STATUS_WAIT_AUDIT = 0;
    const STATUS_FAIL = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_WAIT_PAYMENT = 4;
    const STATUS_PROCESS = 1;
    const STATUS_LIST = [
        self::STATUS_WAIT_AUDIT => "待审核",
        self::STATUS_PROCESS => "正在处理",
        self::STATUS_FAIL => '审核不通过',
        self::STATUS_COMPLETE => '完成',
        self::STATUS_WAIT_PAYMENT => '待打款',
    ];

    public function getStatusTextAttr($value, $data)
    {
        if (isset($data['status']) && isset(self::STATUS_LIST[$data['status']])) {
            return self::STATUS_LIST[$data['status']];
        }
    }

    public function account()
    {
        return $this->hasOne(MerchantAccount::class, 'merchant_account_id', 'account_id');
    }


}
