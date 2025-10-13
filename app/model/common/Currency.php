<?php
namespace app\model\common;

use think\Model;
use utils\Util;

class Currency extends Model
{
    protected $pk = 'id';
    protected $table = 'currency';


	// 货币名称语言翻译
	public function getNameAttr($value)
	{
		if (!empty($value)) {
			return Util::lang($value);
		}
		return "";
	}
}
