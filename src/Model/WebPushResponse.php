<?php

namespace BenTools\WebPushBundle\Model;

final class WebPushResponse
{
    const SUCCESS = 201;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const GONE = 410;
    const PAYLOAD_SIZE_TOO_LARGE = 413;
    const TOO_MANY_REQUESTS = 429;

    /**
     * @var string
     */
    private $subscriptionHash;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * WebPushResponse constructor.
     * @param string $subscriptionHash
     * @param int    $statusCode
     */
    public function __construct(string $subscriptionHash, int $statusCode)
    {
        $this->subscriptionHash = $subscriptionHash;
        $this->statusCode = $statusCode;
    }

    /**
     * @return string
     */
    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return in_array($this->statusCode, [self::NOT_FOUND, self::GONE]);
    }

    /**
     * @return bool
     */
    public function isSuccessFul(): bool
    {
        return self::SUCCESS === $this->statusCode;
    }
}
