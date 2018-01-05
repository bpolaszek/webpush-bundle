# Webpush Bundle

This bundle leverages [minishlink/web-push](https://github.com/web-push-libs/web-push-php) library to associate your Symfony users with Webpush subscriptions.

This way you can integrate push messages into your app to send notifications.

We assume you have a minimum knowledge of how Push Notifications work, otherwise we highly recommend you to read [Matt Gaunt's Web Push Book](https://web-push-book.gauntface.com/).

## Use cases

* You have a todolist app - notify users they're assigned a task
* You have an eCommerce app:
    * Notify your customer their order has been shipped
    * Notify your category manager they sell a product


## Getting started

Because there can be different User implementations, and that some front-end is implied, there are several steps to follow to get started:
1. Install the bundle and its assets
2. Create your own `UserSubscription` class and its associated manager
3. Update your `config.yml` and `routing.yml`
4. Insert a JS snippet in your twig views.

Let's go!

-------------

### Composer is your friend:

PHP7.1+ is required.

```bash
composer require bentools/webpush-bundle 0.2.*
```

_We aren't on stable version yet - expect some changes._


### Add the bundle to your kernel:
```php
# app/AppKernel.php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new BenTools\WebPushBundle\WebPushBundle(),
        ];

        return $bundles;
    }
}
```

### Install assets:

```bash
php bin/console assets:install --symlink
```
_We provide a service worker and a JS client._


### Generate your VAPID keys:

```bash
php bin/console webpush:generate:keys
```

--------

<p align="center">
:arrow_down: :arrow_down: :arrow_down: :arrow_down: <b>Now roll up your sleeves!</b> :arrow_down: :arrow_down: :arrow_down: :arrow_down:
</p>


--------


### Let's do some code

Basically, a user can have several subscriptions (you can log in from several browsers), and a single subscription can be shared among multiple users (you can log in with several accounts on the same browser).

We need to store these associations.

#### Create your UserSubscription class

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


#### Create your UserSubscription manager

Then, create a class that implements `BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface` with your own logic.

Example:
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

#### Configure the bundle:

```yaml
# app/config/config.yml
bentools_webpush:
    settings:
        public_key: '%bentools_webpush.public_key%'
        private_key: '%bentools_webpush.private_key%'
    associations:
        my_users:
            user_class: AppBundle\Entity\User
            user_subscription_class: AppBundle\Entity\UserSubscription
            manager: '@AppBundle\Services\UserSubscriptionManager' # Manager service id
```

#### Update your router:
```yaml
# app/config/routing.yml
bentools_webpush:
    resource: '@WebPushBundle/Resources/config/routing.xml'
    prefix: /webpush
```

#### Update your templates:

Insert this snippet in the templates where your user is logged in:

```twig
<script src="{{ asset('bundles/webpush/js/webpush_client.js') }}" data-webpushclient></script>
<script>
    var webpush = new BenToolsWebPushClient({
        serverKey: '{{ bentools_pusher.server_key | e('js') }}',
        url: '{{ path('bentools_webpush.subscription') }}'
    });
</script>
```

_This will install the service worker and prompt your users to accept notifications._


#### Now, send notifications!


```php
# src/AppBundle/Services/NotificationSender.php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use BenTools\WebPushBundle\Model\Message\Notification;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use Minishlink\WebPush\WebPush;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NotificationSender implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param User $user
     */
    public function sendAwesomeNotification(User $user)
    {
        $sender = $this->container->get(WebPush::class);
        $managers = $this->container->get(WebPushManagerRegistry::class);
        $myUserManager = $managers->getManager($user);
        foreach ($myUserManager->findByUser($user) as $subscription) {
            $sender->sendNotification(
                $subscription->getEndpoint(),
                $this->createAwesomeNotification(),
                $subscription->getPublicKey(),
                $subscription->getAuthToken()
            );
        }
        $sender->flush();
    }

    /**
     * @return Notification
     */
    private function createAwesomeNotification(): Notification
    {
        return new Notification([
            'title' => 'Awesome title',
            'body'  => 'Symfony is great!',
            'icon'  => 'https://symfony.com/logos/symfony_black_03.png',
            'data'  => [
                'link' => 'https://www.symfony.com',
            ],
        ]);
    }
}
```

## FAQ
    
**Do I need FOSUserBundle?**

Nope. We rely on your `Symfony\Component\Security\Core\User\UserInterface` implementation, which can come from **FOSUserBundle** or anything else.


**What if I have several kind of users in my app?**

It's OK. You can subscribe separately your `Employees` and your `Customers`, for instance.

Example config:

```yaml
# app/config/config.yml
bentools_webpush:
    settings:
        public_key: '%bentools_webpush.public_key%'
        private_key: '%bentools_webpush.private_key%'
    associations:
        employees:
            user_class: AppBundle\Entity\Employee
            user_subscription_class: AppBundle\Entity\EmployeeSubscription
            manager: '@AppBundle\Services\EmployeeSubscriptionManager' 
        customers:
            user_class: AppBundle\Entity\Customer
            user_subscription_class: AppBundle\Entity\CustomerSubscription
            manager: '@AppBundle\Services\CustomerSubscriptionManager' 
```


**Can a user subscribe with multiple browsers?**

Of course. You send a notification to a user, it will be dispatched among the different browsers they subscribed.

You can control subscriptions on the client-side.

**How do I manage subscriptions / unsubscriptions from an UI point of view?**

```twig
<script>
    var webpush = new BenToolsWebPushClient({
        serverKey: '{{ bentools_pusher.server_key | e('js') }}', // Required parameter
        url: '{{ path('bentools_webpush.subscription') }}', // Required parameter
        promptIfNotSubscribed: false // Defaults true - setting this to false will disable automatic prompt
    });
    
    webpush.subscribe(); // Prompts the user to allow notifications, then registers the user / subscription on the server.
    webpush.unsubscribe(); // Unregisters the user / subscription association on the server.
    webpush.revoke(); // Invalidates the active subscription, and unregisters the user / subscription association on the server.
    webpush.getNotificationPermissionState(); // granted / prompt / denied
    webpush.askPermission(); // Prompts the user to allow or deny notifications.
</script>
```

**How do I handle expired subscriptions?**

When you push a notification, you can know which endpoints failed.
After pushing, you can retrieve the corresponding recipients and manage their deletion, for instance with Doctrine:

```php
foreach ($manager->findByUser($user) as $subscription) {
    $webpush->sendNotification(
        $subscription->getEndpoint(),
        'ho hi',
        $subscription->getPublicKey(),
        $subscription->getAuthToken()
    );
}

$results = $webpush->flush();

if (is_array($results)) {
    foreach ($results as $result) {
        if (!empty($result['expired'])) {
            foreach ($manager->findByHash($manager->hash($result['endpoint'])) as $subscription) {
                $manager->delete($subscription);
            }
        }
    }
}
```

## Tests

We mostly need functionnal tests. Contributions are very welcome!

## License

MIT