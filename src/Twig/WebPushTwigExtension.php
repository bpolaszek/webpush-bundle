<?php

namespace BenTools\WebPushBundle\Twig;

use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use Twig\Extension\AbstractExtension;
use Twig_Extension_GlobalsInterface;

final class WebPushTwigExtension extends AbstractExtension implements Twig_Extension_GlobalsInterface
{
    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var WebPushManagerRegistry
     */
    private $registry;

    public function __construct(
        string $publicKey,
        ?WebPushManagerRegistry $registry = null
    ) {
        $this->publicKey = $publicKey;
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function getGlobals()
    {
        return [
            'bentools_webpush' => [
                'server_key' => $this->publicKey,
            ],
        ];
    }
}
