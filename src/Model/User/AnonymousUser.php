<?php


namespace BenTools\WebPushBundle\Model\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class is used to support anonymous user subscription
 *
 * @internal
 */
class AnonymousUser implements UserInterface
{
    public function getRoles()
    {
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
    }

    public function eraseCredentials()
    {
    }
}
