<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- APP版本管理
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\image\Image;
use app\service\admin\setting\AppVersionService;
use app\service\admin\setting\ConfigService;
use think\App;
use think\Response;

/**
 * APP版本管理控制器
 */
class AppVersion extends AdminBaseController
{
    protected AppVersionService $appVersionService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param AppVersionService $appVersionService
     */
    public function __construct(App $app, AppVersionService $appVersionService)
    {
        parent::__construct($app);
        $this->appVersionService = $appVersionService;
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'androidVersion',
            'iosVersion',
            'iosLink',
            'androidLink',
        ]);

        return $this->success(
            $item
        );
    }

    /**
     * 添加
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function create(): Response
    {
        $data = $this->request->only([
            'ios_version' => '',
            'android_version' => '',
            'ios_link' => '',
            'android_link' => '',
            'hot_update_link' => '',
            'hot_update_type' => '',
        ], 'post');

        $result = $this->appVersionService->createAppVersion($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error('APP版本管理添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $data = $this->request->all();
        if(empty($data['androidVersion']) || empty($data['iosVersion'])) {
            return $this->error(/** LANG */ '请填写版本号');
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $data['androidVersion']) ||
            !preg_match('/^\d+\.\d+\.\d+$/', $data['iosVersion'])) {
            return $this->error('安卓或IOS版本号格式不正确，请使用类似 1.0.0 的格式');
        }


        $result = app(ConfigService::class)->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 文件上传
     * @return Response
     */
    public function uploadFile(): Response
    {
        if (request()->file('file')) {
            $file = request()->file('file');
            $image = new Image($file, 'update', 'app');
            $original_img = $image->save();
            return $this->success($original_img);
        } else
        {
            return $this->error('文件上传失败！');
        }
    }
}
