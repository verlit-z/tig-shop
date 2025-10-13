<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 面板
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\panel;

use app\adminapi\AdminBaseController;
use app\service\admin\authority\AuthorityService;
use app\service\admin\panel\SalesStatisticsService;
use think\App;
use think\Response;

/**
 * 面板控制器
 */
class Panel extends AdminBaseController
{

    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->checkAuthor('panelManage'); //权限检查
    }

    /**
     * 首页面板-控制台
     *
     * @return Response
     */
    public function index(): Response
    {
        // 控制台数据
        $console_data = app(SalesStatisticsService::class)->getConsoleData($this->shopId);

        // 实时数据
        $real_time_data = app(SalesStatisticsService::class)->getRealTimeData($this->shopId);
        //统计图表
        $panel_statistical_data = app(SalesStatisticsService::class)->getPanelStatisticalData($this->shopId);
        return $this->success([
            'console_data' => $console_data,
            'real_time_data' => $real_time_data,
            'panel_statistical_data' => $panel_statistical_data,
        ]);
    }

    /**
     * 一键直达
     *
     * @return Response
     */
    public function searchMenu(): Response
    {
        $keyword =$this->request->all('keyword', '');
        $keyword = trim($keyword);
		$admin_type = request()->adminType;
        $item = app(AuthorityService::class)->getAuthorityList($keyword,$admin_type);
        return $this->success(
            $item
        );
    }

    public function vendorIndex(): Response
    {

        $vendor_id = request()->vendorId;
        if ($vendor_id <= 0) {
            return $this->error('无效的供应商ID');
        }

        $res = app(SalesStatisticsService::class)->getPanelVendorIndex($vendor_id);

        return $this->success($res);

    }
}
