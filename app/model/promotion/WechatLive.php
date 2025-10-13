<?php

namespace app\model\promotion;

use think\Model;
use utils\Time;

class WechatLive extends Model
{
	protected $pk = 'wechat_live_id';
	protected $table = 'wechat_live';
	protected $json = ['act_range_ext', 'product_data'];
	protected $jsonAssoc = true;
	protected $updateTime = "last_update_time";
	protected $autoWriteTimestamp = true;

	const LIVE_STATUS_START = 101;
	const LIVE_STATUS_NOT_STATUS = 102;
	const LIVE_STATUS_END = 103;
	const LIVE_STATUS_BAN = 104;
	const LIVE_STATUS_PAUSE = 105;
	const LIVE_STATUS_EXCEPTION = 106;
	const LIVE_STATUS_EXPIRED = 107;
	const LIVE_STATUS_MAP = [
		self::LIVE_STATUS_START => '直播中',
		self::LIVE_STATUS_NOT_STATUS => '未开始',
		self::LIVE_STATUS_END => '已结束',
		self::LIVE_STATUS_BAN => '禁播',
		self::LIVE_STATUS_PAUSE => '暂停',
		self::LIVE_STATUS_EXCEPTION => '异常',
		self::LIVE_STATUS_EXPIRED => '已过期'
	];

	// 正常状态的直播
	const LIVE_STATUS_NORMAL = [
		self::LIVE_STATUS_START,
		self::LIVE_STATUS_NOT_STATUS
	];

	const ACT_RANGE_ALL = 0;
	const ACT_RANGE_CATEGORY = 1;
	const ACT_RANGE_BRAND = 2;
	const ACT_RANGE_PRODUCT = 3;
	const ACT_RANGE_MAP = [
		self::ACT_RANGE_ALL => '全部商品',
		self::ACT_RANGE_CATEGORY => '指定分类',
		self::ACT_RANGE_BRAND => '指定品牌',
		self::ACT_RANGE_PRODUCT => '指定商品'
	];

	public function getStartTimeAttr($value)
	{
		return Time::format($value);
	}

	public function getEndTimeAttr($value)
	{
		return Time::format($value);
	}

	public function getLiveStatusTextAttr($value, $data)
	{
		return self::LIVE_STATUS_MAP[$data['live_status']] ?? "";
	}

	public function getActRangeTextAttr($value, $data)
	{
		return  self::ACT_RANGE_MAP[$data['act_range']] ?? "";
	}
}