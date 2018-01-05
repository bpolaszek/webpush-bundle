The UserSubscription Manager will handle creation and persistence of `UserSubscription` entities.

It can be connected to Doctrine, or you're free to use your own logic.

## Create your UserSubscription manager

Then, create a class that implements `BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface` with your own logic.

Example with Doctrine:
```php
# src/AppBundle/Services/UserSubscriptionManager.php

namespace AppBundle\Services;

use AppBundle\Entity\UserSubscription;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class UserSubscriptionManager implements UserSubscriptionManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * UserSubscriptionManager constructor.
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritDoc
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription): UserSubscriptionInterface
    {
        return new UserSubscription($user, $subscriptionHash, $subscription);
    }
    
    /**
     * @inheritDoc
     */
    public function hash(string $endpoint): string {
        return md5($endpoint); // Encode it as you like    
    }

    /**
     * @inheritDoc
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface
    {
        return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findOneBy([
            'user' => $user,
            'subscriptionHash' => $subscriptionHash,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findByUser(UserInterface $user): iterable
    {
        return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findBy([
            'user' => $user,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findByHash(string $subscriptionHash): iterable
    {
        return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findBy([
            'subscriptionHash' => $subscriptionHash,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function save(UserSubscriptionInterface $userSubscription): void
    {
        $this->doctrine->getManager()->persist($userSubscription);
        $this->doctrine->getManager()->flush();
    }

    /**
     * @inheritDoc
     */
    public function delete(UserSubscriptionInterface $userSubscription): void
    {
        $this->doctrine->getManager()->remove($userSubscription);
        $this->doctrine->getManager()->flush();
    }

}
```

Previous: [The UserSubscription Class](01%20-%20The%20UserSubscription%20Class.md)

Next: [Configuration](03%20-%20Configuration.md)