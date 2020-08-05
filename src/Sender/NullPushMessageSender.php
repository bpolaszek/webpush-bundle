<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\PushMessage;

final class NullPushMessageSender implements PushMessagerSenderInterface
{
    /**
     * Does nothing.
     *
     * {@inheritdoc}
     */
    public function push(PushMessage $message, iterable $subscriptions): iterable
    {
        return [];
    }
}
