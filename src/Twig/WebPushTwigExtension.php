<?php

namespace BenTools\WebPushBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;

final class WebPushTwigExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @inheritDoc
     */
    public function getGlobals()
    {
        $publicKey = $this->container->getParameter('bentools_webpush.public_key');
        return [
            'bentools_pusher' => [
                'server_key' => $publicKey ?? null,
            ],
        ];
    }
}
