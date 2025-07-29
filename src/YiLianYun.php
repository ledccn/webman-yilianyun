<?php

namespace Ledc\YiLianYun;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;
use RuntimeException;
use support\Cache;
use support\Log;
use Symfony\Component\Cache\Exception\CacheException;
use Throwable;

/**
 * 易联云打印
 * @author david <367013672@qq.com>
 */
class YiLianYun
{
    /**
     * 易联云配置
     * @var Config
     */
    private Config $config;
    /**
     * 缓存
     * @var CacheInterface
     */
    private CacheInterface $cache;
    /**
     * 日志
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * 单例
     * @var YiLianYun|null
     */
    protected static ?YiLianYun $instance = null;

    /**
     * 构造函数
     * @param Config $config
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    final public function __construct(Config $config, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 获取易联云配置
     * @return Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * 获取缓存实例
     * @return CacheInterface
     */
    final public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * @return LoggerInterface
     */
    final public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 获取缓存键
     * @return string
     */
    final protected function generateCacheKey(): string
    {
        return 'yly_access_token_' . $this->getConfig()->getClientId();
    }

    /**
     * 获取过期时间戳缓存键
     * @return string
     */
    final protected function generateCacheTimestampKey(): string
    {
        return $this->generateCacheKey() . '_timestamp';
    }

    /**
     * 获取系统参数
     * @return array
     */
    protected function systemParams(): array
    {
        $timestamp = time();
        return [
            'client_id' => $this->getConfig()->getClientId(),
            'timestamp' => $timestamp,
            'sign' => $this->getConfig()->signature($timestamp),
            'id' => Config::generateUUID4(),
        ];
    }

    /**
     * 构造附加的请求参数
     * @param bool $withAccessToken 是否携带access_token
     * @param bool $withMachineCode 是否携带machine_code
     * @return array
     */
    protected function withParams(bool $withAccessToken, bool $withMachineCode): array
    {
        return [
            'access_token' => $withAccessToken ? $this->getAccessToken() : '',
            'machine_code' => $withMachineCode ? $this->config->getMachineCode() : '',
        ];
    }

    /**
     * 合并请求参数
     * @param array $data 接口参数
     * @param bool $withAccessToken 是否携带access_token
     * @param bool $withMachineCode 是否携带machine_code
     * @return array
     */
    protected function mergeParams(array $data, bool $withAccessToken, bool $withMachineCode): array
    {
        $params = array_merge(
            $this->systemParams(),
            $this->withParams($withAccessToken, $withMachineCode),
            $data,
        );
        return array_filter($params, fn($value) => null !== $value && '' !== $value && [] !== $value);
    }

    /**
     * POST请求
     * @param string $uri 请求地址
     * @param array $data 用户接口参数（不含系统参数）
     * @param bool $withAccessToken 是否携带access_token
     * @param bool $withMachineCode 是否携带machine_code
     * @return array
     */
    final public function post(string $uri, array $data = [], bool $withAccessToken = true, bool $withMachineCode = true): array
    {
        // 过滤空值
        $data = array_filter($data, fn($value) => null !== $value && '' !== $value && [] !== $value);
        // 合并请求参数：系统参数、附加的请求参数、用户接口参数
        $payload = $this->mergeParams($data, $withAccessToken, $withMachineCode);
        // 发送请求并解析响应，返回结果
        return $this->parseHttpResponse($this->request($uri, $payload));
    }

    /**
     * CURL请求
     * @param string $uri 请求地址
     * @param array $payload 所有请求数据
     * @return HttpResponse
     */
    final public function request(string $uri, array $payload): HttpResponse
    {
        $uniqid = uniqid('', true);
        // 请求日志
        $this->writeDebugLog($uniqid, '【请求】' . $uri, $payload);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=utf-8']);
        if (parse_url(Config::BASE_URL, PHP_URL_SCHEME) === 'https') {
            //false 禁止 cURL 验证对等证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //0 时不检查名称（SSL 对等证书中的公用名称字段或主题备用名称（Subject Alternate Name，简称 SNA）字段是否与提供的主机名匹配）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_URL, Config::BASE_URL . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConfig()->getTimeout());
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getConfig()->getTimeout());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // 自动跳转，跟随请求Location
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);         // 递归次数
        $response = curl_exec($ch);

        $result = new HttpResponse(
            $response,
            (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            curl_errno($ch),
            curl_error($ch)
        );
        curl_close($ch);
        // 响应日志
        $this->writeDebugLog($uniqid, '【响应】' . $uri, $result->jsonSerialize());

        return $result;
    }

    /**
     * 调试模式写日志
     * @param string $uniqueId
     * @param string $uri
     * @param array $context
     * @return void
     */
    protected function writeDebugLog(string $uniqueId, string $uri, array $context): void
    {
        if ($this->getConfig()->isDebug()) {
            $this->getLogger()->debug($uniqueId, ['uri' => $uri, 'context' => $context]);
        }
    }

    /**
     * 解析HTTP响应
     * @param HttpResponse $httpResponse
     * @return array
     */
    final public function parseHttpResponse(HttpResponse $httpResponse): array
    {
        if ($httpResponse->isFailed()) {
            throw new RuntimeException('CURL请求易联云接口失败：' . $httpResponse->toJson(JSON_UNESCAPED_UNICODE));
        }

        $response = json_decode($httpResponse->getResponse(), true);
        $status = $response['error'] ?? -1;
        $msg = $response['error_description'] ?? '';
        if (0 === $status) {
            $timestamp = $response['timestamp'] ?? 0;
            $body = $response['body'] ?? [];
            return [$body, $timestamp];
        }

        $message = (string)($msg ?: $httpResponse->getResponse());
        throw new RuntimeException('易联云接口返回错误：' . $message);
    }

    /**
     * 获取易联云调用凭证AccessToken
     * @return string
     */
    final public function getAccessToken(): string
    {
        try {
            $accessToken = $this->getCache()->get($this->generateCacheKey());
            if ($accessToken && is_string($accessToken)) {
                return $accessToken;
            }

            $body = $this->oauth();
            return $body['access_token'];
        } catch (Throwable $throwable) {
            $this->getLogger()->error('获取易联云AccessToken失败：' . $throwable->getMessage());
            throw new RuntimeException('获取易联云AccessToken失败：' . $throwable->getMessage());
        }
    }

    /**
     * 定时任务
     * @return void
     */
    public function scheduler(): void
    {
        try {
            $accessToken = $this->getCache()->get($this->generateCacheKey());
            $expiresTime = $this->getCache()->get($this->generateCacheTimestampKey()) ?: 0;
            if (empty($accessToken) || ($expiresTime < time() + 300)) {
                $this->oauth();
            }
        } catch (Throwable $throwable) {
            $this->getLogger()->error('易联云定时刷新access_token执行失败：' . $throwable->getMessage());
        }
    }

    /**
     * 获取易联云打印实例
     * @param array $config
     * @return static
     * @throws Throwable
     * @throws CacheException
     */
    final public static function make(array $config): static
    {
        $cache = Cache::store();
        $logger = Log::channel();
        return new static(new Config($config), $cache, $logger);
    }

    /**
     * 获取易联云打印实例（单例模式）
     * @return static
     * @throws CacheException
     * @throws Throwable
     */
    final public static function getInstance(): static
    {
        if (!static::$instance) {
            static::$instance = static::make(Config::getConfig('app.config'));
        }
        return static::$instance;
    }

    /**
     * 获取易联云授权
     * @return array
     * @throws PsrInvalidArgumentException
     */
    final public function oauth(): array
    {
        [$body, $timestamp] = $this->post('/oauth/oauth', [
            'grant_type' => 'client_credentials',
            'scope' => 'all',
        ], false, false);
        $accessToken = $body['access_token'] ?? '';
        if (empty($accessToken)) {
            throw new RuntimeException('字段access_token值为空');
        }

        if ($this->getConfig()->isDebug()) {
            $this->getLogger()->debug('易联云授权成功' . json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $expires_in = (int)$body['expires_in'];
        $this->getCache()->set($this->generateCacheKey(), $accessToken, $expires_in);
        $this->getCache()->set($this->generateCacheTimestampKey(), time() + $expires_in, $expires_in);
        return $body;
    }

    /**
     * 文本打印
     * - 文本打印识别的指令非HTML，详细请看 指令文档说明 https://www.kancloud.cn/ly6886/oauth-api/3170341
     * - 字符支持：中文（简体、繁体）、英文字母、日文、俄文、标点符号、阿拉伯数字、运算符号（+-×÷%）、货币符号（￥$）；其余字符提供有限支持，请测试确认无误后再在生产环境使用
     * @param string $content 打印内容
     * @param string $origin_id 开发者侧，订单id，内容自定义，64个字节内
     * @param string $machine_code 终端号
     * @param int|null $idempotence 幂等处理（默认1，传入本参数，会根据origin_id进行幂等处理，2小时内相同origin_id会返回上一次的结果）
     * @return array
     */
    final public function print(string $content, string $origin_id, string $machine_code = '', ?int $idempotence = null): array
    {
        [$body, $timestamp] = $this->post('/print/index', [
            'content' => $content,
            'origin_id' => $origin_id,
            'machine_code' => $machine_code ?: $this->getConfig()->getMachineCode(),
            'idempotence' => $idempotence,
        ]);
        return $body;
    }

    /**
     * 图片打印
     * - 图片打印只能识别jpg、jpeg、png格式的图片，推荐使用jpg、jpeg格式。
     * - 关于打印图片宽度说明，58mm纸宽的打印纸下图片的宽度像素不能超过384px。m与px之间的换算比例：1mm≈8px
     * - 图片大小限制说明，58mm纸宽的打印纸下图片大小计算：(像素宽/8)*像素高 < 100kb，对于K5下图片大小计算：(像素宽/8)*像素高 < 200kb
     * @param string $picture_url 在线图片链接
     * @param string $origin_id 开发者侧，订单id，内容自定义，64个字节内
     * @param string $machine_code 终端号
     * @param int|null $idempotence 幂等处理（默认1，传入本参数，会根据origin_id进行幂等处理，2小时内相同origin_id会返回上一次的结果）
     * @return array
     */
    final public function picturePrint(string $picture_url, string $origin_id, string $machine_code = '', ?int $idempotence = null): array
    {
        [$body, $timestamp] = $this->post('/pictureprint/index', [
            'picture_url' => $picture_url,
            'origin_id' => $origin_id,
            'machine_code' => $machine_code ?: $this->getConfig()->getMachineCode(),
            'idempotence' => $idempotence,
        ]);
        return $body;
    }
}
