<?php

namespace BenTools\WebPushBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class WebPushExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('bentools_webpush.vapid_subject', $config['settings']['subject'] ?? $container->getParameter('router.request_context.host'));
        $container->setParameter('bentools_webpush.vapid_public_key', $config['settings']['public_key'] ?? null);
        $container->setParameter('bentools_webpush.vapid_private_key', $config['settings']['private_key'] ?? null);
        $loader = new XmlFileLoader($container, new FileLocator([__DIR__.'/../Resources/config/']));
        $loader->load('services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'bentools_webpush';
    }
}
