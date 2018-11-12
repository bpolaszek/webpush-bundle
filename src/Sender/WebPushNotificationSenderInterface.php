<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\WebPushMessage;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\WebPushResponse;

interface WebPushNotificationSenderInterface
{

    const RESPONSE_SUCCESS = 201;
    const RESPONSE_TOOMANYREQUESTS = 201;

    /**
     * Push a notification.
     * The implementation MUST adapt the payload with proper padding, etc.
     *
     * @param string   $payload
     * @param UserSubscriptionInterface[] $subscribers
     * @return WebPushResponse[]
     */
    public function push(WebPushMessage $message, iterable $subscribers): iterable;
}
