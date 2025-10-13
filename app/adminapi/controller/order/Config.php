<?php

namespace app\adminapi\controller\order;

use app\adminapi\AdminBaseController;
use app\service\admin\order\OrderConfigService;
use think\App;
use think\Response;

/**
 * 店铺订单配置
 */
class Config  extends AdminBaseController
{
    protected OrderConfigService $orderConfigService;
    /**
     * 构造函数
     *
     * @param App $app
     * @param OrderConfigService $orderConfigService
     */
    public function __construct(App $app, OrderConfigService $orderConfigService)
    {
        parent::__construct($app);
        $this->orderConfigService = $orderConfigService;
    }

    /**
     * 获取配置详情
     * @return Response
     */
    public function detail(): Response
    {
        $code = input('code', "");
        $shop_id = input('shopId', "");
        $config = $this->orderConfigService->getDetail($code, $shop_id);
        return $this->success(
            $config
        );
    }

    /**
     * 配置保存
     * @return Response
     */
    public function save(): Response
    {
        $data = $this->request->only([
            'shop_id/d' => 0,
            'date_type/d' => 0,
            'use_day/d' => 0,
            'code' => '',
        ]);
        $shop_id = $data['shop_id'];

//        $data['code'] = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $data['code']));
        $result = $this->orderConfigService->saveConfig($data['code'], $data, $shop_id);
        return $result ? $this->success() : $this->error(/** LANG */ '设置项更新失败');
    }
}