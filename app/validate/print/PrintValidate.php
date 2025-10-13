<?php
//**---------------------------------------------------------------------+
//** 验证器文件 -- 打印管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\validate\print;

use app\model\print\Printer;
use think\Validate;
class PrintValidate extends Validate
{
    protected $rule = [
        'print_name' => 'require|max:250',
        'print_sn' => 'require|max:250',
        'print_key' => 'require|max:250',
        'third_account' => 'require|max:250',
        'third_key' => 'require|max:250',
        'print_number' => 'number|between:1,1000',
        'platform' => 'number|between:1,100',
        'shop_id' => 'number',
        'status' => 'number|in:1,2',
    ];

    protected $message = [
        'print_name.require' => '打印机名称不能为空',
        'print_name.max' => '打印机名称最多250个字符',
        'print_sn.require' => '打印机SN不能为空',
        'print_sn.max' => '打印机SN最多250个字符',
    //    'print_sn.checkUnique' => '打印机SN已存在',
        'print_key.require' => '打印机key不能为空',
        'print_key.max' => '打印机key最多250个字符',
        'third_account.require' => '第三方平台对接账号名称不能为空',
        'third_account.max' => '第三方平台对接账号名称最多250个字符',
        'third_key.require' => '第三方平台key不能为空',
        'third_key.max' => '第三方平台key最多250个字符',
        'print_number.number' => '打印联数必须是数字',
        'print_number.between' => '打印联数必须在1到1000之间',
        'platform.number' => '平台id必须是数字',
        'platform.between' => '平台id在1到100之间',
        'shop_id.number' => '店铺ID必须是数字',
        'status.number' => '状态必须是数字',
        'status.in' => '状态在1到2之间',
    ];

    // 验证唯一
    protected function checkUnique(string $print_sn, int $platform)
    {
        $query = Printer::where('print_sn', $print_sn)->where('platform', $platform)->where('delete_time', 0);
        return $query->count() === 0;
    }
}
