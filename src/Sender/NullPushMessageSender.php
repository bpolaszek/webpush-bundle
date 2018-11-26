<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\PushMessage;

final class NullPushMessageSender implements PushMessagerSenderInterface
{
    /**
     * Does nothing.
     *
     * @inheritDoc
     */
    public function push(PushMessage $message, iterable $subscriptions): iterable
    {
        return [];
    }
}
