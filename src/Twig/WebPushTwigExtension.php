<?php

namespace BenTools\WebPushBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class WebPushTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ?string $publicKey,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        return [
            'bentools_webpush' => [
                'server_key' => $this->publicKey,
            ],
        ];
    }
}
