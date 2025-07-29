<?php

namespace Ledc\YiLianYun;

use InvalidArgumentException;
use Throwable;

/**
 * 易联云配置管理类
 * @author david <367013672@qq.com>
 */
class Config
{
    /**
     * 接口地址
     */
    public const string BASE_URL = 'https://open-api.10ss.net/v2';
    /**
     * 应用ID
     * @var string
     */
    readonly protected string $client_id;
    /**
     * 应用密钥
     * @var string
     */
    readonly protected string $client_secret;
    /**
     * 【生产】易联云打印机：终端号（非必填）
     * @var string
     */
    protected string $machine_code_prod = '';
    /**
     * 【测试】易联云打印机：终端号（非必填）
     * @var string
     */
    protected string $machine_code_test = '';
    /**
     * 易联云开放平台开发者ID（非必填）
     * @var string
     */
    protected string $develop_id = '';
    /**
     * 易联云打印超时时间
     * @var int
     */
    protected int $timeout = 10;
    /**
     * 易联云打印总开关
     * @var bool
     */
    protected bool $enabled = false;
    /**
     * 易联云当前运行环境
     * @var bool
     */
    protected bool $debug = true;

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key) && isset($value)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * 应用ID
     * @return string
     */
    public function getClientId(): string
    {
        return $this->client_id;
    }

    /**
     * 应用密钥
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->client_secret;
    }

    /**
     * 获取生产环境终端号
     * @return string
     */
    public function getMachineCodeProd(): string
    {
        return $this->machine_code_prod;
    }

    /**
     * 获取测试环境终端号
     * @return string
     */
    public function getMachineCodeTest(): string
    {
        return $this->machine_code_test;
    }

    /**
     * 获取终端号
     * @return string
     */
    public function getMachineCode(): string
    {
        return $this->isDebug() ? $this->getMachineCodeTest() : $this->getMachineCodeProd();
    }

    /**
     * 获取易联云开放平台开发者ID
     * @return string
     */
    public function getDevelopId(): string
    {
        return $this->develop_id;
    }

    /**
     * 获取请求超时时间
     * @return int
     */
    public function getTimeout(): int
    {
        return max($this->timeout, 3);
    }

    /**
     * 获取易联云打印总开关
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取易联云当前运行环境
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * 设置易联云当前运行环境
     * @param bool $debug
     * @return Config
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * 获取易联云签名
     * @param string $timestamp
     * @return string
     */
    public function signature(string $timestamp): string
    {
        return strtolower(md5($this->client_id . $timestamp . $this->client_secret));
    }

    /**
     * 生成UUID4
     * @return string
     */
    public static function generateUUID4(): string
    {
        try {
            $data = random_bytes(16);
        } catch (Throwable $throwable) {
            if (!function_exists('openssl_random_pseudo_bytes')) {
                throw new InvalidArgumentException('Cannot generate random bytes');
            }
            $data = openssl_random_pseudo_bytes(16);
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // 设置版本为4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // 设置变体为RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 获取基础插件的app配置
     * @param string|null $key
     * @return array
     */
    public static function getConfig(?string $key = null): array
    {
        $prefix = 'plugin.ledc.yilianyun';
        if (null === $key) {
            return config($prefix . '.app');
        }
        return config($prefix . '.' . ltrim($key, '.'));
    }
}
