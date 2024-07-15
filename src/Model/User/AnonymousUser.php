<?php


namespace BenTools\WebPushBundle\Model\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class is used to support anonymous user subscription
 *
 * @internal
 */
final class AnonymousUser implements UserInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return '';
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return '';
    }
}
