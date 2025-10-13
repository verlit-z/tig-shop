<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- APP版本管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\setting;

use app\model\setting\Config;
use app\service\common\BaseService;
use exceptions\ApiException;
use tig\Http;

/**
 * 授权管理服务类
 */
class LicensedService extends BaseService
{

    public function __construct()
    {
    }


    /**
     * 更新授权
     * @return true
     */
    public function update(string $licensed)
    {
        //改成从官网获取授权码
        //线上
        $url = 'www.tigshop.com/api/user/auth_credentials/check';
        //本地
        //$url = '192.168.5.106:9156/index/user/auth_credentials/check';
        $params = [
            'sn' => $licensed,
        ];
        $res = Http::get($url, $params);
        if(!is_json($res)) {
            throw new ApiException('授权出错！');
        }

        $res_arr = json_decode($res,true);
        if($res_arr['data']['errcode'] > 0) {
            throw new ApiException($res_arr['data']['message']);
        }

        if(!isset($res_arr['data']['licensed'])) {
            throw new ApiException('未获取到有用的授权信息');
        }
        $license = $res_arr['data']['licensed'];
        $insert = [];
        $insert['orderId'] = $license['order_id'] ?? '';
        $insert['licensedType'] = $license['licensedType'];
        $insert['licensedTypeName'] = $license['licensedTypeName'];
        $insert['deCopyright'] = $license['deCopyright'];
        $insert['isEnterprise'] = $license['isEnterprise'];
        $insert['authorizedDomain'] = $license['authorizedDomain'];
        $insert['license'] = $license['license']?? $licensed;
        $insert['holder'] = $license['holder'];
        $insert['releaseTime'] = $license['releaseTime'] ?? 0;
        $insert['licensedId'] = json_encode($license['licensedId']);
        $insert['expirationTime'] = $license['expirationTime'] ?? 0;

        app(ConfigService::class)->saveConfig("auto_generate_licensed_data", $insert);

        return true;
    }

}
