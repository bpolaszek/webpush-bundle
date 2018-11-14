<?php

namespace BenTools\WebPushBundle\Model\Response;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

final class PushResponse
{
    const SUCCESS = 201;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const GONE = 410;
    const PAYLOAD_SIZE_TOO_LARGE = 413;
    const TOO_MANY_REQUESTS = 429;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var UserSubscriptionInterface
     */
    private $subscription;

    /**
     * WebPushResponse constructor.
     * @param UserSubscriptionInterface $subscription
     * @param int                       $statusCode
     */
    public function __construct(UserSubscriptionInterface $subscription, int $statusCode)
    {
        $this->subscription = $subscription;
        $this->statusCode = $statusCode;
    }

    /**
     * @return UserSubscriptionInterface
     */
    public function getSubscription(): UserSubscriptionInterface
    {
        return $this->subscription;
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
