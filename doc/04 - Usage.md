## Now, send notifications!

Here's a sample example of an e-commerce app which will notify both the customer and the related category managers when an order has been placed.

```php
namespace App\Services;

use App\Entity\Employee;
use App\Entity\Order;
use App\Events\OrderEvent;
use App\Events\OrderEvents;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use BenTools\WebPushBundle\Sender\GuzzleClientSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSender implements EventSubscriberInterface
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
        GuzzleClientSender $sender
    ) {
        $this->webPushManagerRegistry = $webPushManagerRegistry;
        $this->sender = $sender;
    }

    public static function getSubscribedEvents()
    {
        return [
            OrderEvents::PLACED => 'onOrderPlaced',
        ];
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderPlaced(OrderEvent $event): void
    {
        $order = $event->getOrder();
        $this->notifyCustomer($order);
        $this->notifyCategoryManagers($order);
    }

    /**
     * @param Order $order
     */
    private function notifyCustomer(Order $order): void
    {
        $customer = $order->getCustomer();
        $subscriptionManager = $this->webPushManagerRegistry->getManager($customer);
        $subscriptions = $subscriptionManager->findByUser($customer);
        $notification = new PushNotification('Congratulations!', [
            PushNotification::BODY => 'Your order has been placed.',
            PushNotification::ICON => '/assets/icon_success.png',
        ]);
        $responses = $this->sender->push($notification->createMessage(), $subscriptions);

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $subscriptionManager->delete($response->getSubscription());
            }
        }
    }

    /**
     * @param Order $order
     */
    private function notifyCategoryManagers(Order $order): void
    {
        $products = $order->getProducts();
        $employees = [];
        foreach ($products as $product) {
            $employees[] = $product->getCategoryManager();
        }

        $employees = array_unique($employees);

        $subscriptionManager = $this->webPushManagerRegistry->getManager(Employee::class);

        $subscriptions = [];
        foreach ($employees as $employee) {
            foreach ($subscriptionManager->findByUser($employee) as $subscription) {
                $subscriptions[] = $subscription;
            }
        }

        $notification = new PushNotification('A new order has been placed!', [
            PushNotification::BODY => 'A customer just bought some of your products.',
            PushNotification::ICON => '/assets/icon_success.png',
        ]);

        $responses = $this->sender->push($notification->createMessage(), $subscriptions);

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $subscriptionManager->delete($response->getSubscription());
            }
        }
    }
}
```

Previous: [Configuration](03%20-%20Configuration.md)

Next: [F.A.Q.](05%20-%20FAQ.md)