<?php

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\product\ProductService;
use app\service\admin\promotion\SignService;
use think\App;
use think\Response;
use utils\Util;

class Sign extends IndexBaseController
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
     * 签到首页
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(): Response
    {
        $user_id = request()->userId;
        $SignService = app(SignService::class);
        $list = $SignService->getSignSettingList();
        $record = $SignService->getSignCount($user_id);
        $is_sign = $SignService->checkUserSignIn($user_id);
        $sign_points = $SignService->getSignPoints($list, $record, $is_sign);
        $recommend_goods = app(ProductService::class)->getFilterResult([
            'size' => 8,
            'intro_type' => 'is_hot',
            'product_status' => 1,
            'is_delete' => 0
        ]);
        return $this->success([
            'days' => $list,
            'record' => $record,
            'is_sign' => $is_sign,
            'sign_points' => $sign_points,
            'recommend_goods' => $recommend_goods
        ]);
    }

    /**
     * 签到
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function signIn(): Response
    {
        $user_id = request()->userId;
        $SignService = app(SignService::class);
        $is_sign = $SignService->checkUserSignIn(request()->userId);
        if ($is_sign) return $this->error(Util::lang('今日已经签到'));
        $list = $SignService->getSignSettingList();
        $all_total = count($list);
        //获取上次记录的行数
        $pre_num = $SignService->getSignCount($user_id);
        if ($all_total > $pre_num) {
            $sign_num = $pre_num + 1;
        } else {
            $sign_num = 1;
        }
        $result = $SignService->addSignIn($user_id, $sign_num);
        if (!$result) return $this->error(Util::lang('签到失败！'));

        return $this->success();
    }
}