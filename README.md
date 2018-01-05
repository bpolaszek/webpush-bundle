# Webpush Bundle

This bundle leverages [minishlink/web-push](https://github.com/web-push-libs/web-push-php) library to associate your Symfony users with Webpush subscriptions.

This way you can integrate push messages into your app to send notifications.

We assume you have a minimum knowledge of how Push Notifications work, otherwise we highly recommend you to read [Matt Gaunt's Web Push Book](https://web-push-book.gauntface.com/).

## Use cases

* You have a todolist app - notify users they're assigned a task
* You have an eCommerce app:
    * Notify your customer their order has been shipped
    * Notify your category manager they sell a product
    

## Summary

1. [Installation](#getting-started)
2. [The UserSubscription entity](doc/01%20-%20The%20UserSubscription%20Class.md)
3. [The UserSubscription manager](doc/02%20-%20The%20UserSubscription%20Manager.md)
4. [Configure the bundle](doc/03%20-%20Configuration.md)
5. [Enjoy!](doc/04%20-%20Usage.md)
6. [F.A.Q.](doc/05%20-%20FAQ.md)

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
composer require bentools/webpush-bundle 0.3.*
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


Next: [Create your UserSubscription class](doc/01%20-%20The%20UserSubscription%20Class.md)

## Tests

We mostly need functionnal tests. Contributions are very welcome!

## License

MIT