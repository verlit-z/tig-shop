<?php

namespace app\model\setting;

use think\Model;

class Album extends Model
{
	protected $pk = 'id';
	protected $table = 'album';
	protected $createTime = "add_time";
	protected $autoWriteTimestamp = true;

	const PIC_TYPE_PICTURE = 1;
	const PIC_TYPE_ICON = 2;
	const PIC_TYPE_MAP = [
		self::PIC_TYPE_PICTURE => '图片',
		self::PIC_TYPE_ICON => '图标'
	];

	public function getPicTypeTextAttr($value,$data)
	{
		return self::PIC_TYPE_MAP[$data['pic_type']] ?? "";
	}

}