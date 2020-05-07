<?php

namespace BenTools\WebPushBundle\Model\Subscription;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserSubscriptionManagerInterface
{
    /**
     * Create a user/subscription association.
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): UserSubscriptionInterface;

    /**
     * Return a string representation of the subscription's endpoint.
     * Example: md5($endpoint).
     */
    public function hash(string $endpoint, UserInterface $user): string;

    /**
     * Return the subscription attached to this user.
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface;

    /**
     * Return the list of known subscriptions for this user.
     * A user can have several subscriptions (on chrome, firefox, etc.).
     *
     * @return iterable|UserSubscriptionInterface[]
     */
    public function findByUser(UserInterface $user): iterable;

    /**
     * Return the list of known subscriptions for this hash.
     * Several users can share the same hash.
     */
    public function findByHash(string $subscriptionHash): iterable;

    /**
     * Store the user/subscription association.
     */
    public function save(UserSubscriptionInterface $userSubscription): void;

    /**
     * Remove the user/subscription association.
     */
    public function delete(UserSubscriptionInterface $userSubscription): void;
}
