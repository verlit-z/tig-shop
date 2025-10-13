<?php

//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 打印机
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\print;

use app\adminapi\AdminBaseController;
use app\service\admin\print\PrintService;
use app\validate\print\PrintValidate;
use think\App;

/**
 * 打印机控制器
 */
class Printer extends AdminBaseController
{
    protected PrintService $printService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param PrintService $printService
     */
    public function __construct(App $app, PrintService $printService)
    {
        parent::__construct($app);
        $this->printService = $printService;
    }


    public function create()
    {

        $requestData = $this->request->only([
            'print_name/s' => '',
            'print_sn/s' => '',
            'print_key/s' => '',
            'third_account/s' => '',
            'third_key/s' => '',
            'print_number/d' => 1,
            'auto_print' => 1,
            'platform/d' => 1,
            'shop_id/d' => 0,
            'status/d' => 2,
        ]);
        $requestData['print_number'] = 1; // 固定值1  暂时不启用这个编辑功能
        $requestData['shop_id'] = request()->shopId;
        $keys = array_keys($requestData);
        validate(PrintValidate::class)->only($keys)->check($requestData);
        $result = $this->printService->create($requestData);
        if ($result) {
            return $this->success('创建成功');
        } else {
            return $this->error('创建失败');
        }
    }

    public function update()
    {

        $id = $this->request->post('print_id/d', 0);
        if ($id <= 0) {
            return $this->error('无效的ID');
        }
        $requestData = $this->request->only([
            'print_name/s' => '',
            'print_sn/s' => '',
            'print_key/s' => '',
            'third_account/s' => '',
            'third_key/s' => '',
            'print_number/d' => 1,
            'platform/d' => 1,
       //     'shop_id/d' => 0,
            'status/d' => 1,
        ]);

        $requestData['print_number'] = 1; // 固定值1  暂时不启用这个编辑功能
     //   $requestData['shop_id'] = request()->shopId;
        $keys = array_keys($requestData);
        validate(PrintValidate::class)->only($keys)->check($requestData);

        $result = $this->printService->update($id, $requestData);
        if ($result) {
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }


    public function list()
    {
        $filter = $this->request->only([
            'keyword' => '',
            'page/d' => 1,
            'size/d' => 15,
            'status/d' => 0,
            'sort_field' => 'add_time',
            'sort_order' => 'desc',
            'print_name' => '',
            'print_sn' => '',
            'platform/d' => 0,
        ], 'get');
        $filter['shop_id'] = request()->shopId;
        $map = ['print_id', 'print_name', 'print_sn', 'platform', 'status', 'add_time', 'update_time'];
        $filterResult = $this->printService->getFilterResult($filter, $map);
        foreach ($filterResult as &$item) {
            $item['status_text'] = $this->printService->getStatusText($item['status']);
            $item['platform_text'] = $this->printService->getPlatformText($item['platform']);
        }
        $total = $this->printService->getFilterCount($filter);
        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    public function detail()
    {

        $id = $this->request->get('id/d', 0);
        if ($id <= 0) {
            return $this->error('无效的打印机ID');
        }
        $map = ['*'];
        $info = $this->printService->detail($id,$map);
        if (empty($info)) {
            return $this->error('打印机不存在');
        }
        $info['status_text'] = $this->printService->getStatusText($info['status']);
        $info['platform_text'] = $this->printService->getPlatformText($info['platform']);
        return $this->success($info);

    }

    public function updateField()
    {
        $id =$this->request->all('id/d', 0);
        if ($id <= 0) {
            return $this->error('无效的打印机ID');
        }
        $field = $this->request->all('field', '');
        $val = $this->request->all('val', null);

        $allowedFields = ['status'];
        if (!in_array($field, $allowedFields)) {
            return $this->error('不允许更新的字段：' . $field);
        }

        if ($val === null) {
            return $this->error('缺少更新值 val');
        }

        $data = [
            $field => $val,
        ];

        $shop_id = request()->shopId;

        $res = $this->printService->updateField($id, $shop_id, $data);

        if (!$res) {
            return $this->error('更新失败');
        }

        return $this->success();
    }

    public function delete()
    {
        $id =$this->request->all('id/d', 0);
        if ($id <= 0) {
            return $this->error('无效的打印机ID');
        }
        $res = $this->printService->del($id);

        if (!$res) {
            return $this->error('删除失败');
        }

        return $this->success();
    }


    public function hasEnabled()
    {
        $shop_id = request()->shopId;
        $res = $this->printService->hasEnabled($shop_id);
        if ($res) {
            return $this->success();
        }
        return $this->error();
    }

}