<?php

namespace BenTools\WebPushBundle\DependencyInjection;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class WebPushCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $registry = $container->getDefinition(UserSubscriptionManagerRegistry::class);
        $taggedSubscriptionManagers = $container->findTaggedServiceIds('bentools_webpush.subscription_manager');
        foreach ($taggedSubscriptionManagers as $id => $tag) {
            if (!isset($tag[0]['user_class'])) {
                throw new \InvalidArgumentException(sprintf('Missing user_class attribute in tag for service %s', $id));
            }
            $registry->addMethodCall('register', [$tag[0]['user_class'], new Reference($id)]);
        }
    }
}
