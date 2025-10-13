<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'autoEndConversation' => 'app\command\AutoEndConversation',
        'batchTranslation' => 'app\command\BatchTranslation',
        'refreshConfig:3.0.0' => 'app\command\updateVersion\RefreshConfig'
    ],
];
