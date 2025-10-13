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
use app\validate\setting\AppVersionValidate;
use exceptions\ApiException;
use log\AdminLog;

/**
 * APP版本管理服务类
 */
class AppVersionService extends BaseService
{
    protected AppVersionValidate $appVersionValidate;

    public function __construct()
    {
    }

    /**
     * 获取详情
     * @return Config
     * @throws ApiException
     */
    public function getDetail(): Config
    {
        $result = Config::where('biz_code', "app_version")->find();

        if (!$result) {
            throw new ApiException('APP版本管理不存在');
        }

        return $result;
    }

    /**
     * 数据配置
     * @param array $data
     * @return array
     */
    public function getCommonVersionData(array $data): array
    {
        $config = [
            'data' => [
                'android_version' => $data["android_version"],
                'ios_version' => $data["ios_version"],
                'ios_link' => $data["ios_link"],
                'android_link' => $data["android_link"],
                'hot_update_link' => $data["hot_update_link"],
                'hot_update_type' => $data["hot_update_type"],
            ],
        ];
        return $config;
    }

    /**
     * 执行APP版本管理添加
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public function createAppVersion(array $data): int
    {
        $app_version = Config::where("code", "app_version")->find();
        $config = $this->getCommonVersionData($data);

        if(!empty($app_version)){
            throw new ApiException(/** LANG */'配置已存在，请勿重复添加！');
        }else{
            $config["code"] = 'app_version';
            // 添加
            $result = Config::create($config);
            AdminLog::add('新增APP版本管理:' . $data['android_version']);
            return $result->getKey();
        }
    }



    /**
     * 执行APP版本管理更新
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateAppVersion(int $id, array $data): bool
    {
        $app_version = Config::where("code", "app_version")->find();
        $config = $this->getCommonVersionData($data);

        if(empty($app_version)){
            throw new ApiException(/** LANG */'该配置不存在，请先添加配置！');
        }else{
            $app_version = $app_version->toArray();
            $config["data"]["ios_link"] = !empty($data["ios_link"]) ? $data["ios_link"] : $app_version["data"]["ios_link"];
            $config["data"]["android_link"] = !empty($data["android_link"]) ? $data["android_link"] : $app_version["data"]["android_link"];
            $config["data"]["hot_update_link"] = !empty($data["hot_update_link"]) ? $data["hot_update_link"] : $app_version["data"]["hot_update_link"];
            // 修改
            $result = Config::where('code', "app_version")->save($config);
            AdminLog::add('更新APP版本管理:' . $data['android_version']);
            return $result !== false;
        }
    }
}
