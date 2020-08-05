[![Latest Stable Version](https://poser.pugx.org/bentools/webpush-bundle/v/stable)](https://packagist.org/packages/bentools/webpush-bundle)
[![License](https://poser.pugx.org/bentools/webpush-bundle/license)](https://packagist.org/packages/bentools/webpush-bundle)
[![Build Status](https://img.shields.io/travis/bpolaszek/webpush-bundle/master.svg?style=flat-square)](https://travis-ci.org/bpolaszek/webpush-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/webpush-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/webpush-bundle)
[![Total Downloads](https://poser.pugx.org/bentools/webpush-bundle/downloads)](https://packagist.org/packages/bentools/webpush-bundle)

# Webpush Bundle

This bundle allows your app to leverage [the Web Push protocol](https://developers.google.com/web/fundamentals/push-notifications/web-push-protocol) to send notifications to your users' devices, whether they're online or not.

With a small amount of code, you'll be able to associate your [Symfony users](https://symfony.com/doc/current/security.html#a-create-your-user-class) to WebPush Subscriptions:

* A single user can subscribe from multiple browsers/devices
* Multiple users can subscribe from a single browser/device

This bundle uses your own persistence system (Doctrine or anything else) to manage these associations.

We assume you have a minimum knowledge of how Push Notifications work, otherwise we highly recommend you to read [Matt Gaunt's Web Push Book](https://web-push-book.gauntface.com/).

**Example Use cases**

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

This bundle is just the back-end part of the subscription process. For the front-end part, have a look at the [webpush-client](https://www.npmjs.com/package/webpush-client) package.

### Composer is your friend:

PHP7.1+ is required.

```bash
composer require bentools/webpush-bundle 0.6.*
```

If you're using Symfony 3, add the bundle to your kernel. With Symfony Flex, this should be done automatically.

⚠️ _We aren't on stable version yet - expect some changes._



### Generate your VAPID keys:

```bash
php bin/console webpush:generate:keys
```

You'll have to update your config with the given keys. We encourage you to store them in environment variables or in `parameters.yml`.


Next: [Create your UserSubscription class](doc/01%20-%20The%20UserSubscription%20Class.md)

## Tests

> ./vendor/bin/phpunit

## License

MIT

## Credits

This bundle leverages the [minishlink/web-push](https://github.com/web-push-libs/web-push-php) library.
