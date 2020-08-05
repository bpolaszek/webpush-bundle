<?php

namespace BenTools\WebPushBundle\Model\Subscription;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserSubscriptionInterface
{
    /**
     * Return the user associated to this subscription.
     */
    public function getUser(): UserInterface;

    /**
     * Return the hash of this subscription. Can be a fingerprint or a cookie.
     */
    public function getSubscriptionHash(): string;

    /**
     * Return the subscriber's HTTP endpoint.
     */
    public function getEndpoint(): string;

    /**
     * Return the subscriber's public key.
     */
    public function getPublicKey(): string;

    /**
     * Return the subscriber auth token.
     */
    public function getAuthToken(): string;

    /**
     * Content-encoding (default: aesgcm).
     */
    public function getContentEncoding(): string;
}
