<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use Symfony\Component\Security\Core\User\UserInterface;

final class TestUser implements UserInterface
{
    public function __construct(private string $userName)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->userName;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
    }
}
