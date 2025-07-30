# 简介

易联云打印开放平台SDK，webman基础插件

## 安装

`composer require ledc/yilianyun`

webman v2版本使用本插件时，需要安装缓存组件：`composer require -W webman/cache`

## 配置与使用

可以在 `.env` 文件中配置环境变量

```conf
YLY_CLIENT_ID=易联云开放平台：应用ID
YLY_CLIENT_SECRET=易联云开放平台：应用密钥
YLY_MACHINE_CODE=易联云打印机：终端号
YLY_DEVELOP_ID=易联云开放平台：开发者ID（非必填）
YLY_ENABLED=1
YLY_DEBUG=0
```

使用单例模式调用：

```php
use Ledc\YiLianYun\YiLianYun;

// 文本打印
$result = YiLianYun::getInstance()->print('测试打印', time());

// 图片打印
$result = YiLianYun::getInstance()->picturePrint('https://www.baidu.com/img/bd_logo1.png', time());

// 直接调用POST方法，底层自动处理签名逻辑、AccessToken。
$result = YiLianYun::getInstance()->post('易联云接口地址', '易联云接口数组参数', true, true);
```

## 使用说明

```php
use Ledc\YiLianYun\Config;
use Ledc\YiLianYun\YiLianYun;
use support\Cache;
use support\Log;

$config = new Config([
    'client_id' => '易联云开放平台：应用ID',
    'client_secret' => '易联云开放平台：应用密钥',
    'machine_code_prod' => '【生产】易联云打印机：终端号（非必填）',
    'machine_code_test' => '【测试】易联云打印机：终端号（非必填）',
    'develop_id' => '易联云开放平台：开发者ID（非必填）',
    'timeout' => 10,
    'enabled' => true,
    'debug' => true,
]);

// 创建易联云SDK对象实例
$client = new YiLianYun($config, Cache::store(), Log::channel());

// 在创建SDK对象实例后，所有的方法都可以由IDE自动补全；例如：

// 文本打印
$result = $client->print('测试打印', time());
var_dump($result);

// 图片打印
$result = $client->picturePrint('https://www.baidu.com/img/bd_logo1.png', time());
var_dump($result);

// 直接调用POST方法，底层自动处理签名逻辑、AccessToken。
$result = $client->post('易联云接口地址', '易联云接口数组参数', true, true)
var_dump($result);
```
