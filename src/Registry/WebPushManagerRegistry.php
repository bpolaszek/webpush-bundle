<?php

namespace BenTools\WebPushBundle\Registry;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Doctrine\Common\Util\ClassUtils;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class WebPushManagerRegistry
{
    private $registry = [];

    /**
     * @param string                           $userClass
     * @param UserSubscriptionManagerInterface $userSubscriptionManager
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

        $this->registry[$userClass] = $userSubscriptionManager;
    }

    /**
     * @param UserInterface|string $userClass
     * @return UserSubscriptionManagerInterface
     * @throws RuntimeException
     * @throws ServiceNotFoundException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function getManager($userClass): UserSubscriptionManagerInterface
    {
        if (!is_a($userClass, UserInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected class or object that implements %s, %s given',
                    UserInterface::class,
                    is_object($userClass) ? get_class($userClass) : gettype($userClass)
                )
            );
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
}
