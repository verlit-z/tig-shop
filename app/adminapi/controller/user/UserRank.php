<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 会员等级
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\user;

use app\adminapi\AdminBaseController;
use app\service\admin\user\UserRankService;
use think\App;
use think\Response;

/**
 * 会员等级控制器
 */
class UserRank extends AdminBaseController
{
    protected UserRankService $userRankService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param UserRankService $userRankService
     */
    public function __construct(App $app, UserRankService $userRankService)
    {
        parent::__construct($app);
        $this->userRankService = $userRankService;
    }

    /**
     * 列表
     * @return Response
     */
//    public function listOld(): Response
//    {
//        $filter = $this->request->only([
//            'rank_name' => '',
//            'page' => 1,
//            'size' => 15,
//            'sort_field' => 'rank_id',
//            'sort_order' => 'asc',
//        ], 'get');
//
//        // 获取配置
//        $config = [];
//        if (config('app.IS_PRO')) {
//            $config = app(userRankService::class)->getRankConfig();
//            if (empty($config)) {
//                $data = app(userRankService::class)->defaultRankData();
//                $config = $data['rank_config'];
//            }
//            $filter['rank_type'] = $config['rank_type'];
//        }
//        $filterResult = $this->userRankService->getFilterList($filter, [], ['user_rights', 'user_count']);
//        $total = $this->userRankService->getFilterCount($filter);
//        $filterResultArray = $filterResult->toArray();
//        $res = [];
//        if (!empty($filterResultArray)) {
//            if (!config("app.IS_PRO")) {
//                foreach ($filterResultArray as $key => $item) {
//                    $tmp = [];
//                    $tmp['rank_id'] = $item['rank_id'];
//                    $tmp['user_count'] = $item['user_count'];
//                    $tmp['rank_name'] = $item['rank_name'];
//                    $tmp['rank_logo'] = $item['rank_logo'];
//                    $tmp['rank_level'] = $item['rank_level'];
//                    $res[] = $tmp;
//                }
//            } else {
//                $res = $filterResultArray;
//            }
//        } else {
//            $data = app(userRankService::class)->defaultRankData();
//            if (config("app.IS_PRO")) {
//                $res = $data['user_rank_list'];
//                $total = count($res);
//            } else {
//                $res = $data['user_rank_list_not_pro'];
//
//            }
//        }
//        return $this->success([
//            'records' => $res,
//            'total' => $total,
//            'rank_config' => $config
//        ]);
//    }

    /**
     * 非pro会员会员列表
     * @return Response
     */
    public function list()
    {
        $config = [];
        $filter = $this->request->only([
            'rank_name' => '',
            'page' => 1,
            'size' => 15,
            'sort_field' => 'rank_id',
            'sort_order' => 'asc',
        ], 'get');

        $filterResult = $this->userRankService->getFilterList($filter, [], ['user_rights', 'user_count']);
        $total = $this->userRankService->getFilterCount($filter);
        $res = [];
        if (!empty($filterResult)) {
            foreach ($filterResult as $key => $item) {
                $tmp = [];
                $tmp['rank_id'] = $item['rank_id'];
                $tmp['user_count'] = $item['user_count'];
                $tmp['rank_name'] = $item['rank_name'];
                $tmp['rank_logo'] = $item['rank_logo'];
                $tmp['rank_level'] = $item['rank_level'];
                $res[] = $tmp;
            }
        } else {
            $data = app(userRankService::class)->defaultRankData();
            $res = $data['user_rank_list_not_pro'];
            $total = count($res);
        }
        return $this->success([
            'user_rank' => [
                'records' => $res,
                'total' => $total,
            ],
            'rank_config' => $config
        ]);
    }


    /**
     * 详情
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function detail(): Response
    {
        $rank_type = $this->request->all('rank_type/d', 1);
        $item = $this->userRankService->getDetail($rank_type);
        return $this->success(
            $item
        );
    }

    /**
     * 编辑
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function update(): \think\Response
    {
        $data = $this->request->only([
            "rank_type/d" => 1,
            'data/a' => [],
            'user_rank_config/a' => [],
            'grow_up_setting/a' => []
        ], 'post');

        $result = $this->userRankService->updateUserRank($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('会员等级更新失败');
        }
    }
}
