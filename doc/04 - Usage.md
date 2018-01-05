## Now, send notifications!


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

Previous: [Configuration](03%20-%20Configuration.md)

Next: [F.A.Q.](05%20-%20FAQ.md)