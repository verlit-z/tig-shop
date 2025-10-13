<?php

namespace app\model\user;

use think\Model;
use utils\Time;

class UserCompany extends Model
{
	protected $pk = 'id';
	protected $table = 'user_company';
	protected $createTime = "add_time";
	protected $autoWriteTimestamp = true;
	protected $json = ['company_data'];
	protected $jsonAssoc = true;

	const STATUS_WAIT = 1;
	const STATUS_PASS = 2;
	const STATUS_REFUSE = 3;
	const STATUS_MAP = [
		self::STATUS_WAIT => '待审核',
		self::STATUS_PASS => '审核通过',
		self::STATUS_REFUSE => '审核未通过',
	];

    const TYPE_PERSON = 1;
    const TYPE_COMPANY = 2;
    const TYPE_MAP = [
        self::TYPE_PERSON => '个人',
        self::TYPE_COMPANY => '企业',
    ];

    public function getTypeTextAttr($value, $data)
    {
        return self::TYPE_MAP[$data['type']] ?? "";
    }

	public function getStatusTextAttr($value, $data)
	{
		return self::STATUS_MAP[$data['status']] ?? "";
	}


	// 关联用户
	public function user()
	{
		return $this->hasOne(User::class, 'user_id', 'user_id')
			->field(['user_id','username','mobile','is_company_auth']);
	}

	public function getAuditTimeAttr($value)
	{
		return Time::format($value);
	}
}