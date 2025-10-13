<?php
declare (strict_types = 1);

namespace app\command;

use app\im\service\conversation\ConversationService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class AutoEndConversation extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('autoEndConversation')
            ->setDescription('the autoEndConversation command');
    }

    protected function execute(Input $input, Output $output)
    {
        app(ConversationService::class)->autoEndConversation();
        $output->writeln('success');
    }
}
