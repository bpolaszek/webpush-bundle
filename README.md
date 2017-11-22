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
2. Create your own `UserDevice` class and its associated manager
3. Update your `config.yml` and `routing.yml`
4. Insert a JS snippet in your twig views.

Let's go!

-------------

### Composer is your friend:

PHP7.1+ is required.

```bash
composer require bentools/webpush-bundle 1.0.x-dev
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

#### Create your UserDevice class

First, you have to implement `BenTools\WebPushBundle\Model\Device\UserDeviceInterface`. 

It's an entity which associates:
1. Your user entity
2. A device hash (i.e. a browser fingerprint) - our JS lib provides it
3. The subscription details - it will store the JSON representation of the `Subscription` javascript object.

Example class:
```php
# src/AppBundle/Entity/UserDevice.php

namespace AppBundle\Entity;

use BenTools\WebPushBundle\Model\Device\UserDeviceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity()
 */
class UserDevice implements UserDeviceInterface
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
    private $deviceHash;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $subscription;

    /**
     * UserDevice constructor.
     * @param User   $user
     * @param string $deviceHash
     * @param array  $subscription
     */
    public function __construct(User $user, string $deviceHash, array $subscription)
    {
        $this->user = $user;
        $this->deviceHash = $deviceHash;
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
    public function getDeviceHash(): string
    {
        return $this->deviceHash;
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


#### Create your UserDevice manager

Then, create a class that implements `BenTools\WebPushBundle\Model\Device\UserDeviceManagerInterface` with your own logic.

Example:
```php
# src/AppBundle/Services/UserDeviceManager.php

namespace AppBundle\Services;

use AppBundle\Entity\UserDevice;
use BenTools\WebPushBundle\Model\Device\UserDeviceInterface;
use BenTools\WebPushBundle\Model\Device\UserDeviceManagerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class UserDeviceManager implements UserDeviceManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * UserDeviceManager constructor.
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritDoc
     */
    public function factory(UserInterface $user, string $deviceHash, array $subscription): UserDeviceInterface
    {
        return new UserDevice($user, $deviceHash, $subscription);
    }

    /**
     * @inheritDoc
     */
    public function getUserDevice(UserInterface $user, string $deviceHash): ?UserDeviceInterface
    {
        return $this->doctrine->getManager()->getRepository(UserDevice::class)->findOneBy([
            'user' => $user,
            'deviceHash' => $deviceHash,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getUserDevices(UserInterface $user): iterable
    {
        return $this->doctrine->getManager()->getRepository(UserDevice::class)->findBy([
            'user' => $user,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function save(UserDeviceInterface $userDevice): void
    {
        $this->doctrine->getManager()->persist($userDevice);
        $this->doctrine->getManager()->flush();
    }

    /**
     * @inheritDoc
     */
    public function delete(UserDeviceInterface $userDevice): void
    {
        $this->doctrine->getManager()->remove($userDevice);
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
            device_class: AppBundle\Entity\UserDevice
            manager: '@AppBundle\Services\UserDeviceManager' # Manager service id
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
<script src="{{ asset('bundles/webpush/js/webpush_client.js') }}"></script>
<script>
    var push = new BenToolsWebPushClient({
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
        $myUserManager = $managers->getManagerFor($user);
        foreach ($myUserManager->getUserDevices($user) as $device) {
            $sender->sendNotification(
                $device->getEndpoint(),
                $this->createAwesomeNotification(),
                $device->getPublicKey(),
                $device->getAuthToken()
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
            device_class: AppBundle\Entity\EmployeeDevice
            manager: '@AppBundle\Services\EmployeeDeviceManager' 
        customers:
            user_class: AppBundle\Entity\Customer
            device_class: AppBundle\Entity\CustomerDevice
            manager: '@AppBundle\Services\CustomerDeviceManager' 
```


**Can a user subscribe with multiple browsers?**

Of course. You send a notification to a user, it will be dispatched among the different browsers they subscribed.

You can control subscriptions on the client-side.

## Tests

We mostly need functionnal tests. Contributions are very welcome!

## License

MIT