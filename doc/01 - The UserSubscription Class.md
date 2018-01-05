Basically:

* A user can have several subscriptions (you can log in from several browsers)
* A single subscription can be shared among multiple users (you can log in with several accounts on the same browser).

We need to store these associations.

## Create your UserSubscription class

First, you have to implement `BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface`. 

It's a simple entity which associates:
1. Your user entity
2. The subscription details - it will store the JSON representation of the `Subscription` javascript object.
3. A hash of the endpoint (or any string that could help in retrieving it).

You're free to use Doctrine or anything else.

Example class:
```php
# src/AppBundle/Entity/UserSubscription.php

namespace AppBundle\Entity;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity()
 */
class UserSubscription implements UserSubscriptionInterface
{

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $subscriptionHash;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $subscription;

    /**
     * UserSubscription constructor.
     * @param User   $user
     * @param string $subscriptionHash
     * @param array  $subscription
     */
    public function __construct(User $user, string $subscriptionHash, array $subscription)
    {
        $this->user = $user;
        $this->subscriptionHash = $subscriptionHash;
        $this->subscription = $subscription;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint(): string
    {
        return $this->subscription['endpoint'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getPublicKey(): string
    {
        return $this->subscription['keys']['p256dh'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAuthToken(): string
    {
        return $this->subscription['keys']['auth'] ?? null;
    }

}
```

Previous: [Installation](../README.md#getting-started)

Next: [The UserSubscription Manager](02%20-%20The%20UserSubscription%20Manager.md)