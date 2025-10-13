<?php

namespace app\adminapi\controller\user;

use app\adminapi\AdminBaseController;
use app\service\admin\user\UserRankLogService;
use think\App;
use think\Response;

class UserRankLog extends AdminBaseController
{
    protected UserRankLogService $userRankLogService;

    public function __construct(App $app, UserRankLogService $userRankLogService)
    {
        parent::__construct($app);
        $this->userRankLogService = $userRankLogService;
    }

    /**
     * 列表
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'username' => '',
            'page' => 1,
            'size' => 15,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->userRankLogService->getFilterList($filter,['user']);
        $total = $this->userRankLogService->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }
}