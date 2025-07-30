<?php

namespace Ledc\YiLianYun;

use JsonSerializable;

/**
 * HTTP响应
 * @author david <367013672@qq.com>
 */
class HttpResponse implements JsonSerializable
{
    /**
     * 响应
     * @var bool|string
     */
    protected string|bool $response;
    /**
     * 响应代码
     * @var int
     */
    protected int $statusCode = 0;
    /**
     * 返回最后一次的错误代码，错误代码或在没有错误发生时返回 0 (零)
     * @var int
     */
    protected int $curlErrorNo = 0;
    /**
     * 返回错误信息，或者如果没有任何错误发生就返回 '' (空字符串)。
     * @var string
     */
    protected string $curlErrorMessage = '';

    /**
     * 构造函数
     * @param bool|string $response 响应
     * @param int $statusCode HTTP状态码
     * @param int $curlErrorNo curl错误代码
     * @param string $curlErrorMessage curl错误信息
     */
    public function __construct(bool|string $response, int $statusCode, int $curlErrorNo, string $curlErrorMessage)
    {
        $this->response = $response;
        $this->statusCode = $statusCode;
        $this->curlErrorNo = $curlErrorNo;
        $this->curlErrorMessage = $curlErrorMessage;
    }

    /**
     * 获取响应体
     * @return bool|string
     */
    public function getResponse(): bool|string
    {
        return $this->response;
    }

    /**
     * 获取响应的HTTP状态码
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取错误代码
     * - 在没有错误发生时返回 0 (零)
     * @return int
     */
    public function getCurlErrorNo(): int
    {
        return $this->curlErrorNo;
    }

    /**
     * 获取错误信息
     * - 没有任何错误发生就返回 '' (空字符串)。
     * @return string
     */
    public function getCurlErrorMessage(): string
    {
        return $this->curlErrorMessage;
    }

    /**
     * 判断响应成功
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return 200 <= $this->getStatusCode() && $this->getStatusCode() < 300;
    }

    /**
     * 判断响应失败啦
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->isSuccessful();
    }

    /**
     * 转数组
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * 转JSON
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * 转为字符串
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_UNESCAPED_UNICODE);
    }
}
