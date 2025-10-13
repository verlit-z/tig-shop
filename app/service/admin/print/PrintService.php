<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 打印机
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\print;

use app\job\PrintJob;
use app\model\print\PrintConfig;
use app\model\print\Printer;
use app\service\admin\print\src\FeiEYunService;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\PrintTemplateConstant;
use utils\TigQueue;

/**
 * 打印机服务类
 */
class PrintService extends BaseService
{
    protected PrintConfig $printConfigModel;
    protected Printer $printerModel;

    public function __construct()
    {
        $this->printConfigModel = new PrintConfig();
        $this->printerModel = new Printer();
    }


    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->printerModel->query()->alias('v');
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('v.print_name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['print_name']) && !empty($filter['print_name'])) {
            $query->where('v.print_name', 'like', '%' . $filter['print_name'] . '%');
        }

        if (isset($filter['shop_id']) && $filter['shop_id'] >= 0) {
            $query->where('v.shop_id', $filter['shop_id']);
        }

        if (isset($filter['platform']) && !empty($filter['platform'])) {
            $query->where('v.platform', $filter['platform']);
        }

        if (isset($filter['print_sn']) && !empty($filter['print_sn'])) {
            $query->where('v.print_sn', $filter['print_sn']);
        }

        if (isset($filter['status']) && $filter['status'] > 0) {
            $query->where('v.status', $filter['status']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        if (isset($filter['print_id'])) {
            $query->whereIn('v.print_id', $filter['print_id']);
        }
        $query->where('v.delete_time', 0);
        return $query;
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @param array $map
     * @return array
     */
    public function getFilterResult(array $filter, array $map = ['*']): array
    {
        $query = $this->filterQuery($filter);
        $result = $query->page($filter['page'], $filter['size'])->field($map)->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 创建
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {

        try {
            // 校验必要字段
            $requiredFields = ['third_account', 'third_key', 'print_sn', 'print_key', 'print_name'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new ApiException("缺少必要字段: {$field}");
                }
            }

            $feiEYunService = app(FeiEYunService::class);
            $time = time();
            $data['add_time'] = $time;
            $data['update_time'] = $time;

            // 生成签名
            $sig = $feiEYunService->signature($data['third_account'], $data['third_key'], $time);

            // 构建打印机信息
            $printerContent = $data['print_sn'] . "#" . $data['print_key'] . "#" . $data['print_name'];

            $print_info = [
                'user' => $data['third_account'],
                'stime' => $time,
                'sig' => $sig,
                'apiname' => 'Open_printerAddlist',
                'printerContent' => $printerContent
            ];

            $result = $feiEYunService->add($print_info);
            if ($result['ret'] === 0 && !empty($result['data']['ok']) || isset($result['data']['no']) && strpos($result['data']['no'][0], '已被添加过')) {
                if ($data['status'] == 1) {
                    $this->printerModel::where('shop_id', $data['shop_id'])->where('status', 1)->update(['status' => 2]);
                }
                $print_id = $this->printerModel->insertGetId($data);
                if ($print_id <= 0) {
                    throw new ApiException('打印机信息插入失败');
                }
                if ($this->initConfig($print_id)) {
                    return true;
                }

                return false;
            }
            throw new ApiException($result['msg'] ?? '未知错误');
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }

    }

    /**
     * 更新
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data)
    {

        if (!$id) {
            throw new ApiException('#id错误');
        }
        if (!is_array($data) ||
            !isset($data['print_sn'], $data['print_key'], $data['third_account'], $data['third_key'], $data['print_name'])) {
            throw new ApiException('参数缺失');
        }

        try {
            $print = $this->printerModel::find($id);
            if (!$print) {
                throw new ApiException('打印机不存在');
            }

            // 判断是否需要重新注册打印机
            $needReRegister = (
                $print['print_sn'] != $data['print_sn'] ||
                $print['print_key'] != $data['print_key'] ||
                $print['third_account'] != $data['third_account'] ||
                $print['third_key'] != $data['third_key']
            );

            if ($needReRegister) {
                $feiEYunService = app(FeiEYunService::class);
                $time = time();
                $sig = $feiEYunService->signature($print['third_account'], $print['third_key'], $time);

                $print_info = [
                    'user' => $print['third_account'],
                    'stime' => $time,
                    'sig' => $sig,
                    'apiname' => 'Open_printerDelList',
                    'snlist' => $print['print_sn']
                ];

                $result = $feiEYunService->delete($print_info);
                if (!is_array($result) || !isset($result['ret']) || $result['ret'] != 0) {
                  //  throw new ApiException('删除旧打印机失败');
                    // 继续执行，因为可能是打印机在飞鹅云平台已经不存在
                }

                $time = time();
                $sig = $feiEYunService->signature($data['third_account'], $data['third_key'], $time);

                $printerContent = $data['print_sn'] . "#" . $data['print_key'] . "#" . $data['print_name'];

                $print_info = [
                    'user' => $data['third_account'],
                    'stime' => $time,
                    'sig' => $sig,
                    'apiname' => 'Open_printerAddlist',
                    'printerContent' => $printerContent
                ];

                $result = $feiEYunService->add($print_info);
                if ($result['ret'] != 0 || empty($result['data']['ok'])) {
                    throw new ApiException('更新打印机失败');
                }
            }

            if ($data['status'] == 1 && $data['status']!= $print['status']) {
                $this->printerModel::where('shop_id', $print['shop_id'])->where('status', 1)->update(['status' => 2]);
            }

            $result = $print->save($data);
            return $result !== false;
        } catch (\Exception $e) {
            // 可记录日志或做其他处理
            throw new ApiException('更新失败: ' . $e->getMessage());
        }

    }


    /**
     * 获取详情
     * @param int $id
     * @param array $map
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(int $id,array $map = ['*'])
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $model = $this->printerModel::field($map)->find($id);
        if (is_null($model)) {
            return [];
        }
        return $model->toArray();
    }


    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateField(int $id, int $shop_id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }

        try {

            $print = $this->printerModel::find($id);
            if (!$print) {
                throw new ApiException('打印机不存在');
            }

            if (isset($data['status']) && $data['status'] == 1) {
                $this->printerModel::where('shop_id', $shop_id)->where('status', 1)->update(['status' => 2]);
            }

            $result = $print->save($data);
            return $result !== false;
        } catch (\Exception $e) {
            // 可记录日志或做其他处理
            throw new ApiException('更新失败: ' . $e->getMessage());
        }
    }


    public function del(int $id)
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }

        try {

            $print = $this->printerModel::find($id);
            if (!$print) {
                throw new ApiException('打印机不存在');
            }

            $data = [
                'delete_time' => time(),
            ];
            $result = $print->save($data);

            if (!$result) {
                throw new ApiException('删除失败');
            }
            //-- 注销去第三方删除打印机功能
//            $feiEYunService = app(FeiEYunService::class);
//            $time = time();
//            $sig = $feiEYunService->signature($print['third_account'], $print['third_key'], $time);
//
//            $print_info = [
//                'user' => $print['third_account'],
//                'stime' => $time,
//                'sig' => $sig,
//                'apiname' => 'Open_printerDelList',
//                'snlist' => $print['print_sn']
//            ];
//            $feiEYunService->delete($print_info);
            return $result;
        } catch (\Exception $e) {
            // 可记录日志或做其他处理
            throw new ApiException('删除失败: ' . $e->getMessage());
        }
    }


    public function initConfig(int $print_id, int $type = 1)
    {

        $config = $this->printConfigModel::where('print_id', $print_id)->count();
        if ($config > 0) {
            return true;
        }

        // 创建默认购物小票配置
        $data = [
            'print_id' => $print_id,
            'type' => $type,
            'template' => PrintTemplateConstant::init(),
        ];
        return $this->printConfigModel->save($data);
    }


    public function getStatusText(int $status): string
    {
        $data = [
            1 => '开启',
            2 => '关闭',
        ];
        return $data[$status] ?? '未知状态';
    }

    public function getPlatformText(int $status): string
    {
        $data = [
            1 => '飞鹅云',
        ];
        return $data[$status] ?? '未知平台';
    }

    public function getPrintTypeDesc(int $type)
    {
        $data = [
            1 => '购物小票',
        ];
        return $data[$type] ?? '未知小票';
    }


    public function getConfigsByPrintId(int $id,array $map= ['*']):array
    {
        $info = $this->printConfigModel::field($map)->where('print_id', $id)->find();
        if (empty($info)) {
            return [];
        }
        return $info->toArray();
    }


    public function updateConfigs(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }

        try {
            $print = $this->printConfigModel::find($id);
            if (!$print) {
                throw new ApiException('打印机配置不存在');
            }
            $data['template'] = json_encode($data['template'], JSON_UNESCAPED_UNICODE);
            $result = $print->save($data);
            return $result !== false;
        } catch (\Exception $e) {
            // 可记录日志或做其他处理
            throw new ApiException('更新失败: ' . $e->getMessage());
        }

    }


    public function hasEnabled(int $shop_id=0):bool
    {
        $count = $this->printerModel::where('status', 1)->where('shop_id', $shop_id)->count();
        return $count > 0;
    }

    /**
     *  打印订单
     * @param int $shop_id
     * @param array $ids
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printOrder(int $shop_id = 0, array $ids = []): bool
    {
        $print = $this->printerModel::where('shop_id', $shop_id)->where('status', 1)->find();
        if (!$print) {
            throw new ApiException('打印机不存在');
        }

        //---- 方便测试打印模版
//        $config = $this->printConfigModel::where('print_id', $print['print_id'])->find();
//
//        $service = new FeiEYunService();
//        $content = $service->generatePrintContent(2133, $config['template']);
//        echo $content;
//        exit();

        foreach ($ids as $id) {
            app(TigQueue::class)->push(PrintJob::class, [
                'print' => $print,
                'order_id' => $id
            ]);
        }
        return true;

    }


    /**
     * 异步打印订单
     * @param int $shop_id
     * @param array $ids
     * @return bool
     */
    public function asyncPrintOrder(int $shop_id = 0, array $ids = []): bool
    {
        $print = $this->printerModel::where('shop_id', $shop_id)->where('status', 1)->find();
        if (!$print || $print['auto_print'] != 1) {   // 没有开启自动打印订单功能 就取消
            return false;
        }
        foreach ($ids as $id) {
            app(TigQueue::class)->push(PrintJob::class, [
                'print' => $print,
                'order_id' => $id
            ]);
        }
        return true;

    }

}
