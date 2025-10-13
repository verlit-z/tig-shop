<?php
declare (strict_types = 1);

namespace app\command;

use app\service\admin\lang\TranslationsService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class BatchTranslation extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('BatchTranslation')
            ->setDescription('the BatchTranslation command');
    }

    protected function execute(Input $input, Output $output)
    {
		if (app(TranslationsService::class)->batchTranslation()) {
			// 指令输出
			$output->writeln('success');
		} else {
			$output->writeln('fail');
		}
    }
}
