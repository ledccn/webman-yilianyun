<?php
/**
 * 易联云配置
 */

$defaultMachineCode = getenv('YLY_MACHINE_CODE') ?: '';

return [
    'enable' => true,
    // 易联云配置
    'config' => [
        // 应用ID
        'client_id' => getenv('YLY_CLIENT_ID') ?: '',
        // 应用密钥
        'client_secret' => getenv('YLY_CLIENT_SECRET') ?: '',
        // 【生产】易联云打印机：终端号
        'machine_code_prod' => getenv('YLY_MACHINE_CODE_PROD') ?: $defaultMachineCode,
        // 【测试】易联云打印机：终端号
        'machine_code_test' => getenv('YLY_MACHINE_CODE_TEST') ?: $defaultMachineCode,
        // 易联云开放平台开发者ID（非必填）
        'develop_id' => getenv('YLY_DEVELOP_ID') ?: '',
        // 易联云打印总开关
        'enabled' => (bool)getenv('YLY_ENABLED'),
        // 易联云当前运行环境
        'debug' => (bool)getenv('YLY_DEBUG'),
    ],
];
