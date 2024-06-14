<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TestUserSubscription implements UserSubscriptionInterface
{
    private string $id;

    public function __construct(
        private UserInterface $user,
        private string $endpoint,
        private string $publicKey,
        private string $authtoken,
        private string $subscriptionHash
    ) {
        $this->id = $user->getUserIdentifier();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getAuthToken(): string
    {
        return $this->authtoken;
    }

    public function getContentEncoding(): string
    {
        return 'aesgcm';
    }
}
