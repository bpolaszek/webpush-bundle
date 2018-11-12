<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TestUserSubscription implements UserSubscriptionInterface
{

    private $id;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $authtoken;

    /**
     * @var string
     */
    private $subscriptionHash;

    public function __construct(
        UserInterface $user,
        string $endpoint,
        string $publicKey,
        string $authtoken,
        string $subscriptionHash
    ) {
        $this->id = $user->getUsername();
        $this->user = $user;
        $this->endpoint = $endpoint;
        $this->publicKey = $publicKey;
        $this->authtoken = $authtoken;
        $this->subscriptionHash = $subscriptionHash;
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
