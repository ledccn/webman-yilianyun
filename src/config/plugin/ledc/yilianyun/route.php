<?php
/**
 * 这个文件会在更新时，强制覆盖
 */

use Ledc\YiLianYun\Config;
use support\Request;
use Webman\Route;

// 获取易联云配置
Route::get('/ledc/yilianyun/config', function (Request $request) {
    $config = Config::getConfig('app.config');
    $data = [
        'client_id' => $config['client_id'] ?? '',
        'machine_code_prod' => $config['machine_code_prod'] ?? '',
        'machine_code_test' => $config['machine_code_prod'] ?? '',
        'develop_id' => $config['develop_id'] ?? '',
        'enabled' => $config['enabled'],
        'debug' => $config['debug'],
    ];
    return json(['code' => 0, 'data' => $data, 'msg' => 'ok']);
});
