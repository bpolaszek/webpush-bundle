## Configuration

#### Configure the bundle:

```yaml
# app/config/config.yml (SF3)
# config/packages/bentools_webpush.yaml (SF4) 
bentools_webpush:
    settings:
        public_key: 'your_public_key'
        private_key: 'your_private_key'
```

#### Update your router:
```yaml
# app/config/routing.yml (SF3)
# config/routing.yaml (SF4)
bentools_webpush:
    resource: '@WebPushBundle/Resources/config/routing.xml'
    prefix: /webpush
```

You will have a new route called `bentools_webpush` which will be the Ajax endpoint for handling subscriptions (POST requests) / unsubscriptions (DELETE requests).

Your VAPID public key is now exposed through Twig's `bentools_webpush.server_key` global variable.

To handle subscriptions/unsubscriptions on the front-end side, have a look at [webpush-client](https://www.npmjs.com/package/webpush-client).

Previous: [The UserSubscription Manager](02%20-%20The%20UserSubscription%20Manager.md)

Next: [Usage](04%20-%20Usage.md)
