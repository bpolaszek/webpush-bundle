<?php

namespace BenTools\WebPushBundle;

use BenTools\WebPushBundle\DependencyInjection\WebPushCompilerPass;
use BenTools\WebPushBundle\DependencyInjection\WebPushExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WebPushBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new WebPushExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new WebPushCompilerPass());
    }
}
