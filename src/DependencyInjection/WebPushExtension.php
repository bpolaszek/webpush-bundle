<?php

namespace BenTools\WebPushBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

// \Symfony\Component\HttpKernel\DependencyInjection\Extension deprecated as of Symfony 8.0+
if (class_exists(\Symfony\Component\DependencyInjection\Extension\Extension::class)) {
    abstract class BaseExtension extends \Symfony\Component\DependencyInjection\Extension\Extension
    {

    }
} else {
    abstract class BaseExtension extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
    {

    }
}

class WebPushExtension extends BaseExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('bentools_webpush.vapid_subject', $config['settings']['subject'] ?? $container->getParameter('router.request_context.host'));
        $container->setParameter('bentools_webpush.vapid_public_key', $config['settings']['public_key'] ?? null);
        $container->setParameter('bentools_webpush.vapid_private_key', $config['settings']['private_key'] ?? null);
        $loader = new YamlFileLoader($container, new FileLocator([__DIR__.'/../Resources/config/']));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'bentools_webpush';
    }
}
