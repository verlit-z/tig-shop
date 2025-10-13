<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 首页
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\decorate;

use app\api\IndexBaseController;
use app\service\admin\decorate\DecorateDiscreteService;
use think\App;
use think\Response;

/**
 * 装修组件控制器
 */
class Discrete extends IndexBaseController
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

    /**
     * 获取开屏广告
     *
     * @return Response
     */
    public function getOpenAdvertising(): Response
    {

        $decorateSn = $this->request->param('decorate_sn', 'openAdvertising');
        $item = app(DecorateDiscreteService::class)->getDetail($decorateSn);
        if (is_null($item)){
            return $this->success();
        }
        return $this->success($item['data']);

    }


}