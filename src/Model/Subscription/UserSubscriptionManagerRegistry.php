<?php

namespace BenTools\WebPushBundle\Model\Subscription;

use Doctrine\Common\Util\ClassUtils;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserSubscriptionManagerRegistry implements UserSubscriptionManagerInterface
{
    /**
     * @var UserSubscriptionManagerInterface[]
     */
    private $registry = [];

    /**
     * @throws \InvalidArgumentException
     */
    public function register(string $userClass, UserSubscriptionManagerInterface $userSubscriptionManager)
    {
        if (!is_a($userClass, UserInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Expected class implementing %s, %s given', UserInterface::class, $userClass));
        }

        if (array_key_exists($userClass, $this->registry)) {
            throw new \InvalidArgumentException(sprintf('User class %s is already registered.', $userClass));
        }

        if (self::class === get_class($userSubscriptionManager)) {
            throw new \InvalidArgumentException(sprintf('You must define your own user subscription manager for %s.', $userClass));
        }

        $this->registry[$userClass] = $userSubscriptionManager;
    }

    /**
     * @param UserInterface|string $userClass
     *
     * @throws RuntimeException
     * @throws ServiceNotFoundException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function getManager($userClass): UserSubscriptionManagerInterface
    {
        if (!is_a($userClass, UserInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Expected class or object that implements %s, %s given', UserInterface::class, is_object($userClass) ? get_class($userClass) : gettype($userClass)));
        }

        if (is_object($userClass)) {
            $userClass = get_class($userClass);
        }

        // Case of a doctrine proxied class
        if (0 === strpos($userClass, 'Proxies\__CG__') && class_exists('Doctrine\Common\Util\ClassUtils')) {
            return $this->getManager(ClassUtils::getRealClass($userClass));
        }

        if (!isset($this->registry[$userClass])) {
            throw new \InvalidArgumentException(sprintf('There is no user subscription manager configured for class %s.', $userClass));
        }

        return $this->registry[$userClass];
    }

    /**
     * {@inheritdoc}
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): UserSubscriptionInterface
    {
        return $this->getManager($user)->factory($user, $subscriptionHash, $subscription, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $endpoint, UserInterface $user): string
    {
        return $this->getManager($user)->hash($endpoint, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface
    {
        return $this->getManager($user)->getUserSubscription($user, $subscriptionHash);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUser(UserInterface $user): iterable
    {
        return $this->getManager($user)->findByUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function findByHash(string $subscriptionHash): iterable
    {
        foreach ($this->registry as $manager) {
            foreach ($manager->findByHash($subscriptionHash) as $userSubscription) {
                yield $userSubscription;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(UserSubscriptionInterface $userSubscription): void
    {
        $this->getManager($userSubscription->getUser())->save($userSubscription);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UserSubscriptionInterface $userSubscription): void
    {
        $this->getManager($userSubscription->getUser())->delete($userSubscription);
    }
}
