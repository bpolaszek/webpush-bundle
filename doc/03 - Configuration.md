## Configuration

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
        url: '{{ url('bentools_webpush.subscription') }}'
    });
</script>
```

_This will install the service worker and prompt your users to accept notifications._

Previous: [The UserSubscription Manager](02%20-%20The%20UserSubscription%20Manager.md)

Next: [Usage](04%20-%20Usage.md)
