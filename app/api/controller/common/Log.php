<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 通用
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\common;

use app\api\IndexBaseController;
use app\service\admin\sys\StatisticsService;
use app\service\api\admin\common\TranslationsService;
use think\App;
use think\Response;

/**
 * log控制器
 */
class Log extends IndexBaseController
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
     *
     *
     * @return Response
     */
    public function index(): Response
    {
        $data = request()->all();
        $data['user'] = request()->userId ? request()->userId : request()->ip();

        app(StatisticsService::class)->log($data);
        return $this->success();
    }

}
