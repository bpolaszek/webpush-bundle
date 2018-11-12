

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
        serverKey: '{{ bentools_webpush.server_key | e('js') }}', // Required parameter
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

Previous: [Usage](04%20-%20Usage.md)