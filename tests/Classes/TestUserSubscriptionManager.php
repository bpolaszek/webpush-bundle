<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

final class TestUserSubscriptionManager implements UserSubscriptionManagerInterface
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    /**
     * @inheritDoc
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): UserSubscriptionInterface
    {
        return new TestUserSubscription(
            $user,
            $subscription['endpoint'],
            $subscription['keys']['p256dh'],
            $subscription['keys']['auth'],
            $subscriptionHash
        );
    }

    /**
     * @inheritDoc
     */
    public function hash(string $endpoint, UserInterface $user): string
    {
        return md5($endpoint);
    }

    /**
     * @inheritDoc
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface
    {
        return $this->doctrine->getManagerForClass(TestUserSubscription::class)->getRepository(TestUserSubscription::class)->findOneBy([
            'user'             => $user,
            'subscriptionHash' => $subscriptionHash,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findByUser(UserInterface $user): iterable
    {
        return $this->doctrine->getManagerForClass(TestUserSubscription::class)->getRepository(TestUserSubscription::class)->findBy([
            'user' => $user,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findByHash(string $subscriptionHash): iterable
    {
        return $this->doctrine->getManagerForClass(TestUserSubscription::class)->getRepository(TestUserSubscription::class)->findBy([
            'subscriptionHash' => $subscriptionHash,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function save(UserSubscriptionInterface $userSubscription): void
    {
        $this->doctrine->getManagerForClass(TestUserSubscription::class)->persist($userSubscription);
        $this->doctrine->getManagerForClass(TestUserSubscription::class)->flush();
    }

    /**
     * @inheritDoc
     */
    public function delete(UserSubscriptionInterface $userSubscription): void
    {
        $this->doctrine->getManagerForClass(TestUserSubscription::class)->remove($userSubscription);
        $this->doctrine->getManagerForClass(TestUserSubscription::class)->flush();
    }
}
