<?php
declare (strict_types=1);

namespace app\command\updateVersion;

use app\im\service\conversation\ConversationService;
use app\model\decorate\Decorate;
use app\model\decorate\DecorateDiscrete;
use app\model\setting\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class RefreshConfig extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('refreshConfig:3.0.0')
            ->setDescription('更新2.2.6到3.0.0的config更新。');
    }

    protected function execute(Input $input, Output $output)
    {
        //处理装修数据
        $decorates = Decorate::where('decorate_id', '>', 0)->select();
        foreach ($decorates as $decorate) {
            $data = $decorate['data'];
            $draftData = $decorate['draft_data'];
            if (!empty($data['moduleList']) && is_array($data['moduleList'])) {
                foreach ($data['moduleList'] as $key => &$module) {
                    $module['type'] = convertUnderline($module['type']);
                }
            }
            if (!empty($draftData['moduleList']) && is_array($draftData['moduleList'])) {
                foreach ($draftData['moduleList'] as $key => &$module) {
                    $module['type'] = convertUnderline($module['type']);
                }
            }
            Decorate::where('decorate_id', $decorate['decorate_id'])->update([
                'data' => json_encode($data),
                'draft_data' => json_encode($draftData),
            ]);
        }
        $decorateDiscretes = DecorateDiscrete::select();
        foreach ($decorateDiscretes as $decorateDiscrete) {
            $data = $decorateDiscrete['data'];
            DecorateDiscrete::where('id', $decorateDiscrete['id'])->update([
                'data' => json_encode($data),
                'decorate_sn' => convertUnderline($decorateDiscrete['decorate_sn']),
            ]);
        }

        $salesmanConfigs = \app\model\salesman\Config::select();
        foreach ($salesmanConfigs as $salesmanConfig) {
            \app\model\salesman\Config::where('id', $salesmanConfig['id'])->update([
                'code' => convertUnderline($salesmanConfig['code']),
            ]);
        }
        //使用thinkphp 的DB方法查询config_2.2.6表中的data字段里的json转成数组
        Config::where('id', '>', 0)->delete();
        DB::name('`config_2.2.6`')->where('code', 'old_base')->delete();
        $configs = DB::name('`config_2.2.6`')->select();
        //循环configs 取出data下面的json，如果不为空转为数组
        foreach ($configs as $config) {
            if ($config['code'] == 'theme_style') {
                $themeStyleData = json_decode($config['data'], true);
                $themeStyleData['themeId'] = $themeStyleData['theme_id'];
                unset($themeStyleData['theme_id']);
                $this->createOrUpdateConfig($config['code'], json_encode($themeStyleData));
                continue;
            }
            if (empty($config['data'])) {
                continue;
            }
            $data = @json_decode($config['data'], true);
            //如果$data不为空，则循环数据
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $this->updateConfig($key, $value, $config['code']);
                }
            }
        }
        $output->writeln('success');
    }


    protected function updateConfig($key, $value, $code)
    {
        if (is_array($value)) {
            //判断$value这数组里面的key是否是一个有效的字符串
            foreach ($value as $k => $v) {
                if (intval($k) == $k) {
                    $value = json_encode($value);
                    $this->createOrUpdateConfig($key, $value);

                    break;
                } else {
                    $this->updateConfig($k, $v, $code);
                }
            }

        } else {
            if ($code == 'base_product_category_decorate') {
                $key = 'productCategoryDecorateType';
            }
            if ($code == 'base_api_company_data') {
                if ($key == 'type') {
                    $key = 'companyDataType';
                }
                if ($key == 'tips') {
                    $key = 'companyDataTips';
                }
            }
            if ($code == 'base_api_wechat') {
                if ($key == 'wechatOauth') {
                    $key = 'openWechatOauth';
                }
            }
            $this->createOrUpdateConfig($key, $value);
        }

    }


    protected function createOrUpdateConfig($key, $value)
    {
        if (Config::where('biz_code', convertUnderline($key))->find()) {
//            Config::where('biz_code', convertUnderline($key))->update([
//                'biz_val' => $value,
//            ]);
        } else {
            Config::create([
                'biz_code' => convertUnderline($key),
                'biz_val' => $value,
            ]);
        }
    }
}
