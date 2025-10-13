<?php

namespace app\validate\authority;

use app\model\authority\AdminUser;
use think\Validate;

class AdminUserValidate extends Validate
{
    protected $rule = [
        'username' => 'require|checkUnique|max:30',
    ];

    protected $message = [
        'username.require' => '管理员名称不能为空',
        'username.max' => '管理员名称最多30个字符',
        'username.checkUnique' => '管理员名称已存在或被商户账号占用',
    ];

    protected $scene = [
        'create' => [
            'username',
        ],
        'update' => [
            'username',
        ],
    ];

    /**
     * 验证唯一
     * @param $value
     * @param $rule
     * @param $data
     * @param $field
     * @return bool
     * @throws \think\db\exception\DbException
     */
    protected function checkUnique($value, $rule, $data = [], $field = ''):bool
    {
        $id = isset($data['admin_id']) ? $data['admin_id'] : 0;
        $query = AdminUser::where('username', $value)->where('admin_id', '<>', $id);
        return $query->count() === 0;
    }
}
