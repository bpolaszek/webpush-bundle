## Now, send notifications!


```php
namespace App\Services;

use BenTools\WebPushBundle\Model\Message\WebPushNotification;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\WebPushResponse;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use BenTools\WebPushBundle\Sender\GuzzleClientSender;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationSender
{
    /**
     * @var WebPushManagerRegistry
     */
    private $webPushManagerRegistry;

    /**
     * @var GuzzleClientSender
     */
    private $sender;

    /**
     * NotificationSender constructor.
     * @param WebPushManagerRegistry $webPushManagerRegistry
     * @param GuzzleClientSender     $sender
     */
    public function __construct(
        WebPushManagerRegistry $webPushManagerRegistry,
        GuzzleClientSender $sender) {
        $this->webPushManagerRegistry = $webPushManagerRegistry;
        $this->sender = $sender;
    }


    /**
     * @param UserInterface $user
     */
    public function sendAwesomeNotification(UserInterface $user)
    {
        $manager = $this->webPushManagerRegistry->getManager($user);
        $subscriptions = $manager->findByUser($user);

        // Get subscriptions as hash => $subscription
        $subscriptions = array_combine(
            array_map(function (UserSubscriptionInterface $subscription): string {
                return $subscription->getSubscriptionHash();
            }, $subscriptions),
            $subscriptions
        );

        $notification = $this->createAwesomeNotification();

        /**
         * @var WebPushResponse[] $responses
         */
        $responses = $this->sender->push($notification->createMessage(), $subscriptions);

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $manager->delete($subscriptions[$response->getSubscriptionHash()]);
            }
        }

    }

    /**
     * @return WebPushNotification
     */
    private function createAwesomeNotification(): WebPushNotification
    {
        return new WebPushNotification('Awesome title', [
            'body' => 'Symfony is great!',
            'icon' => 'https://symfony.com/logos/symfony_black_03.png',
            'data' => [
                'link' => 'https://www.symfony.com',
            ],
        ]);
    }
}
```

Previous: [Configuration](03%20-%20Configuration.md)

Next: [F.A.Q.](05%20-%20FAQ.md)