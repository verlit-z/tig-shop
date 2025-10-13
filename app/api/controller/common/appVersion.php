<?php

namespace app\api\controller\common;

use app\api\IndexBaseController;
use app\service\admin\setting\ConfigService;
use think\App;
use think\Response;

class appVersion extends IndexBaseController
{

    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }
    public function getAppUpdate(): Response
    {
        $type = $this->request->all('type');
        $version = $this->request->all('version');
        if(empty($type) || empty($version)) {
            return $this->error('参数错误');
        }
        if($type == 'android') {
            $config =  app(ConfigService::class)->getConfigByBizCode([
                'androidVersion',
                'androidLink',
            ]);
            if(!empty($config)) {
                //比较 $version
                if(version_compare($version, $config['androidVersion'], '<')) {
                    return $this->success($config['androidLink']);
                }
            } else{
                return $this->success('');
            }


        } elseif ($type == 'ios') {
            $config =  app(ConfigService::class)->getConfigByBizCode([
                'iosVersion',
                'iosLink',
            ]);
            if(!empty($config)) {
                if(version_compare($version, $config['iosVersion'], '<')) {
                    return $this->success($config['iosLink']);
                }
            } else{
                return $this->success('');
            }
        }
        return $this->success('');
    }
}