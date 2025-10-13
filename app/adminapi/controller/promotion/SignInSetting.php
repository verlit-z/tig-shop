<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 积分签到
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\promotion;

use app\adminapi\AdminBaseController;
use app\service\admin\promotion\SignInSettingService;
use app\validate\promotion\SignInSettingValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 积分签到控制器
 */
class SignInSetting extends AdminBaseController
{
    protected SignInSettingService $signInSettingService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param SignInSettingService $signInSettingService
     */
    public function __construct(App $app, SignInSettingService $signInSettingService)
    {
        parent::__construct($app);
        $this->signInSettingService = $signInSettingService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->signInSettingService->getFilterResult($filter);
        $total = $this->signInSettingService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->signInSettingService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 添加
     * @return Response
     */
    public function create()
    {
        $data = $this->request->only([
            'name' => '',
            'points' => '',
            'day_num' => '',
        ], 'post');

        try {
            validate(SignInSettingValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->signInSettingService->createSignInSetting($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'积分签到添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'id' => $id,
            'name' => '',
            'points' => '',
        ], 'post');

        try {
            validate(SignInSettingValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->signInSettingService->updateSignInSetting($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'积分签到更新失败');
        }
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->signInSettingService->deleteSignInSetting($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return Response
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->signInSettingService->deleteSignInSetting($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
