<?php

namespace BenTools\WebPushBundle\Model\Device;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserDeviceManagerInterface
{

    /**
     * Create a user/device association.
     *
     * @param UserInterface $user
     * @param string        $deviceHash
     * @param array         $subscription
     * @return UserDeviceInterface
     */
    public function factory(UserInterface $user, string $deviceHash, array $subscription): UserDeviceInterface;

    /**
     * Return the device attached to this user.
     *
     * @param UserInterface $user
     * @param string        $deviceHash
     * @return UserDeviceInterface|null
     */
    public function getUserDevice(UserInterface $user, string $deviceHash): ?UserDeviceInterface;

    /**
     * Return the list of known devices for this user.
     *
     * @param UserInterface $user
     * @return iterable|UserDeviceInterface[]
     */
    public function getUserDevices(UserInterface $user): iterable;

    /**
     * Store the user/device association.
     *
     * @param UserDeviceInterface $userDevice
     */
    public function save(UserDeviceInterface $userDevice): void;

    /**
     * Remove the user/device association.
     *
     * @param UserDeviceInterface $userDevice
     */
    public function delete(UserDeviceInterface $userDevice): void;
}
