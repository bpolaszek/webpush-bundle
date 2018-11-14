

## FAQ
    
**Do I need FOSUserBundle?**

Nope. We rely on your `Symfony\Component\Security\Core\User\UserInterface` implementation, which can come from **FOSUserBundle** or anything else.


**What if I have several kind of users in my app?**

It's OK. You can subscribe separately your `Employees` and your `Customers`, for instance.

Example config:

```yaml
# app/config/services.yml (SF3)
# config/services.yaml (SF4) 

services:
    App\Services\EmployeeSubscriptionManager:
        class: App\Services\EmployeeSubscriptionManager
        arguments:
            - '@doctrine'
        tags:
            - { name: bentools_webpush.subscription_manager, user_class: 'App\Entity\Employee' }
            
    App\Services\CustomerSubscriptionManager:
        class: App\Services\CustomerSubscriptionManager
        arguments:
            - '@doctrine'
        tags:
            - { name: bentools_webpush.subscription_manager, user_class: 'App\Entity\Customer' }
```


**Can a user subscribe with multiple browsers?**

Of course. You send a notification to a user, it will be dispatched among the different browsers they subscribed.

You can control subscriptions on the client-side.

**How do I manage subscriptions / unsubscriptions from an UI point of view?**

For the front-end part of the subscription / unsubscription process, check-out the [WebPush Client Javascript Library](https://www.npmjs.com/package/webpush-client) that has been designed to work with this bundle.


Previous: [Usage](04%20-%20Usage.md)