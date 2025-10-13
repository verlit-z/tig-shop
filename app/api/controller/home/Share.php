<?php

namespace app\api\controller\home;

use app\api\IndexBaseController;
use app\service\admin\decorate\DecorateShareService;
use think\App;
use think\Response;

class Share extends IndexBaseController
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
     * 装修分享导入
     * @return Response
     */
    public function import(): Response
    {
        //根据链接和参数获取参数配置
        $sn = $this->request->all('sn/s', '');
        $token = $this->request->all('token/s', '');
        $res = app(DecorateShareService::class)->getInfoBySn($sn, $token);
        return $this->success($res
        );
    }
}