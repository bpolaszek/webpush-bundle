<?php

namespace BenTools\WebPushBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class WebPushTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var string
     */
    private $publicKey;

    public function __construct(?string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @inheritDoc
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
