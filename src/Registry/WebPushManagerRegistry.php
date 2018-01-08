<?php

namespace BenTools\WebPushBundle\Registry;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class WebPushManagerRegistry implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $associations = [];

    /**
     * WebPushManagerRegistry constructor.
     * @param array $associations
     */
    public function __construct(ContainerInterface $container, array $associations)
    {
        $this->setContainer($container);
        foreach ($associations as $key => $values) {
            $this->register($key, $values);
        }
    }

    /**
     * @param string $key
     * @param array  $values
     * @throws ServiceNotFoundException
     * @throws RuntimeException
     */
    private function register(string $key, array $values): void
    {
        if (!is_a($values['user_class'], UserInterface::class, true)) {
            throw new RuntimeException(sprintf('User class %s must implement %s', $values['user_class'], UserInterface::class));
        }
        if (!is_a($values['user_subscription_class'], UserSubscriptionInterface::class, true)) {
            throw new RuntimeException(sprintf('User subscription class %s must implement %s', $values['user_subscription_class'], UserSubscriptionInterface::class));
        }
        if (!$this->container->has(ltrim($values['manager'], '@'))) {
            throw new ServiceNotFoundException(sprintf('Service %s not found - or make sure it is public.', $values['manager']));
        }
        $this->associations[$key] = $values;
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

        foreach ($this->associations as $association) {
            if ($association['user_class'] === $userClass) {
                $service = $this->container->get(ltrim($association['manager'], '@'));
                if (!$service instanceof UserSubscriptionManagerInterface) {
                    throw new RuntimeException(sprintf('Service %s must implement %s', $association['manager'], UserSubscriptionManagerInterface::class));
                }
                return $service;
            }
        }

        throw new \InvalidArgumentException(sprintf('Webpush service not found for class %s', $userClass));
    }
}
