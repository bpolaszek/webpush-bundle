<?php

namespace BenTools\WebPushBundle\Model\Subscription;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserSubscriptionManagerInterface
{

    /**
     * Create a user/subscription association.
     *
     * @param UserInterface $user
     * @param string        $subscriptionHash
     * @param array         $subscription
     * @return UserSubscriptionInterface
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription): UserSubscriptionInterface;

    /**
     * Return a string representation of the subscription's endpoint.
     * Example: md5($endpoint).
     *
     * @param array $subscription
     * @return string
     */
    public function hash(string $endpoint): string;

    /**
     * Return the subscription attached to this user.
     *
     * @param UserInterface $user
     * @param string        $subscriptionHash
     * @return UserSubscriptionInterface|null
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface;

    /**
     * Return the list of known subscriptions for this user.
     * A user can have several subscriptions (on chrome, firefox, etc.)
     *
     * @param UserInterface $user
     * @return iterable|UserSubscriptionInterface[]
     */
    public function findByUser(UserInterface $user): iterable;

    /**
     * Return the list of known subscriptions for this hash.
     * Several users can share the same hash.
     *
     * @param string $subscriptionHash
     * @return iterable
     */
    public function findByHash(string $subscriptionHash): iterable;

    /**
     * Store the user/subscription association.
     *
     * @param UserSubscriptionInterface $userSubscription
     */
    public function save(UserSubscriptionInterface $userSubscription): void;

    /**
     * Remove the user/subscription association.
     *
     * @param UserSubscriptionInterface $userSubscription
     */
    public function delete(UserSubscriptionInterface $userSubscription): void;
}
