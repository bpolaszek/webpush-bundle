<?php

namespace BenTools\WebPushBundle\Model\Subscription;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserSubscriptionInterface
{

    /**
     * Return the user associated to this subscription.
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface;

    /**
     * Return the hash of this subscription. Can be a fingerprint or a cookie.
     *
     * @return string
     */
    public function getSubscriptionHash(): string;

    /**
     * Return the subscriber's HTTP endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string;

    /**
     * Return the subscriber's public key.
     *
     * @return string
     */
    public function getPublicKey(): string;

    /**
     * Return the subscriber auth token.
     *
     * @return string
     */
    public function getAuthToken(): string;
}
