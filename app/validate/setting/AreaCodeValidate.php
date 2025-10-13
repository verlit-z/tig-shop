<?php

namespace app\validate\setting;

use app\model\authority\AdminUser;
use app\model\setting\AreaCode;
use think\Validate;

class AreaCodeValidate extends Validate
{
	protected $rule = [
		'name' => 'require|max:10|checkUnique',
		'code' => 'require|max:10|checkUnique',
	];

	protected $message = [
		'name.require' => '区号名称不能为空',
		'name.max' => '区号名称最多10个字符',
		'name.checkUnique' => '区号名称已存在',
		'code.require' => '区号不能为空',
		'code.max' => '区号最多10个字符',
		'code.checkUnique' => '区号已存在',
	];

	protected $scene = [
		'create' => [
			'name',
			'code',
		],
		'update' => [
			'name',
			'code',
		],
	];

	/**
	 * 验证唯一
	 * @param $value
	 * @param $rule
	 * @param $data
	 * @param $field
	 * @return bool
	 */
	protected function checkUnique($value, $rule, $data = [], $field = ''):bool
	{
		$id = isset($data['id']) ? $data['id'] : 0;
		$query = AreaCode::where($field, $value)->where('id', '<>', $id);
		return $query->count() === 0;
	}
}