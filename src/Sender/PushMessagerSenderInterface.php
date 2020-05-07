<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\PushMessage;
use BenTools\WebPushBundle\Model\Response\PushResponse;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

interface PushMessagerSenderInterface
{
    /**
     * Push a notification.
     * The implementation MUST adapt the payload with proper padding, etc.
     *
     * @param UserSubscriptionInterface[] $subscriptions
     *
     * @return PushResponse[]
     */
    public function push(PushMessage $message, iterable $subscriptions): iterable;
}
