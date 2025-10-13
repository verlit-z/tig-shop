<?php

namespace app\job;
use app\model\print\PrintConfig;
use app\service\admin\print\src\FeiEYunService;
use exceptions\ApiException;
use think\facade\Log;

/**
 * 打印订单队列
 */
class PrintJob extends BaseJob
{

    /**
     * @param $data
     * @return bool
     */
    public function doJob($data): bool
    {
        try {
            Log::info("打印开始工作");
            $printMode = new PrintConfig();
            $print = $data['print'];
            $config = $printMode->where('print_id', $print['print_id'])->find();
            if (empty($config)) {
                Log::info("打印配置不存在");
            }


            //判断用哪个打印机平台  1 飞鹅云
            if ($print['platform'] == '1') {
                $service = new FeiEYunService();
                $content = $service->generatePrintContent($data['order_id'], $config['template']);
                $time = time();
                $sig = $service->signature($print['third_account'], $print['third_key'], $time);
                Log::info("打印参数完成");
                Log::info(json_encode($content, JSON_UNESCAPED_UNICODE));
                $print_info = [
                    'user' => $print['third_account'],
                    'stime' => $time,
                    'sig' => $sig,
                    'apiname' => 'Open_printMsg',
                    'sn' => $print['print_sn'],
                    'content' => $content,
                    'times' => $print['print_number'],
                ];
                $res = $service->print($print_info);
                Log::info(json_encode($res, JSON_UNESCAPED_UNICODE));
                return true;
            }
            Log::info("打印成功");
            return true;
        } catch (\Exception $e) {
            Log::info("打印错误");
            Log::info($e->getMessage() . $e->getTraceAsString() . $e->getCode() . $e->getLine() . $e->getFile());
            return false;
        }
    }
}