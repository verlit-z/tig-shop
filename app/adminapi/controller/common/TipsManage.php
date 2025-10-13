<?php
//**---------------------------------------------------------------------+
//**   后台控制器文件 -- 缓存管理
//**---------------------------------------------------------------------+
//**   版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//**   作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//**   提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+
namespace app\adminapi\controller\common;

use app\BaseController;
use think\facade\Cache;
use tig\CacheManager;
use utils\Config as UtilsConfig;

class TipsManage extends BaseController
{

    public function __construct()
    {
    }

    public function list()
    {
        $url = request()->get('url');
        $pcDomain = UtilsConfig::get('pcDomain');
        if ($url && str_contains($url, $pcDomain)) {
            $domainBindStatus = true;
        } else {
            $domainBindStatus = false;
        }
        //密码过于简单
        $result = [];
        if (Cache::get('password_too_simple:' . request()->adminUid)) {
            $passwordTooSimpleStatus = true;
        } else {
            $passwordTooSimpleStatus = false;
        }
        $result[] = [
            'code' => 'passwordTooSimple',
            'status' => $passwordTooSimpleStatus
        ];
        $result[] = [
            'code' => 'domainBind',
            'status' => $domainBindStatus
        ];
        return $this->success($result);
    }
}
