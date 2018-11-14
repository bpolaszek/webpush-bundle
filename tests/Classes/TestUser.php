<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use Symfony\Component\Security\Core\User\UserInterface;

final class TestUser implements UserInterface
{
    /**
     * @var string
     */
    private $userName;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function getUsername()
    {
        return $this->userName;
    }

    public function getRoles()
    {
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }
}
