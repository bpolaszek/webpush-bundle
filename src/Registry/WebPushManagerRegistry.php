<?php

namespace BenTools\WebPushBundle\Registry;

use BenTools\WebPushBundle\Model\Device\UserDeviceInterface;
use BenTools\WebPushBundle\Model\Device\UserDeviceManagerInterface;
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
        if (!is_a($values['device_class'], UserDeviceInterface::class, true)) {
            throw new RuntimeException(sprintf('User device class %s must implement %s', $values['device_class'], UserDeviceInterface::class));
        }
        if (!$this->container->has(ltrim($values['manager'], '@'))) {
            throw new ServiceNotFoundException(sprintf('Service %s not found - or make sure it is public.', $values['manager']));
        }
        $this->associations[$key] = $values;
    }

    /**
     * @param UserInterface $user
     * @return UserDeviceManagerInterface
     * @throws RuntimeException
     */
    public function getManagerFor(UserInterface $user): UserDeviceManagerInterface
    {
        foreach ($this->associations as $association) {
            if ($association['user_class'] === get_class($user)) {
                $service = $this->container->get(ltrim($association['manager'], '@'));
                if (!$service instanceof UserDeviceManagerInterface) {
                    throw new RuntimeException(sprintf('Service %s must implement %s', $association['manager'], UserDeviceManagerInterface::class));
                }
                return $service;
            }
        }
    }
}
