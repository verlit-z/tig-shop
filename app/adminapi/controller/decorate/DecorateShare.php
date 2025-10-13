<?php

namespace app\adminapi\controller\decorate;

use app\adminapi\AdminBaseController;
use app\service\admin\decorate\DecorateShareService;
use exceptions\ApiException;
use think\App;
use think\Response;

class DecorateShare extends AdminBaseController
{

    protected DecorateShareService $decorateShareService;

    public function __construct(APP $app, DecorateShareService $decorateShareService)
    {
        parent::__construct($app);
        $this->decorateShareService = $decorateShareService;
    }

    /**
     * @return \think\Response
     * @throws ApiException
     */
    public function share(): Response
    {
        $filter = $this->request->only([
            'decorate_id/d' => 0,
        ], 'get');
        if(empty($filter['decorate_id'])) {
            throw new ApiException('参数错误!');
        }
        $res = $this->decorateShareService->share($filter['decorate_id']);
        return $this->success(
             $res
        );
    }

    /**
     * @return \think\Response
     * @throws ApiException
     */
    public function import(): Response
    {
        $filter = $this->request->only([
            'url' => '',
        ], 'get');
        if(empty($filter['url'])) {
            throw new ApiException('请输入要导入的链接!');
        }
        $filter['shop_id'] = request()->shopId;
        $res = $this->decorateShareService->import($filter);
        if(!$res) {
            return $this->error('导入模版失败!');
        }
        return $this->success();
    }
}