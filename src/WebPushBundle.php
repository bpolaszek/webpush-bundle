<?php

namespace BenTools\WebPushBundle;

use BenTools\WebPushBundle\DependencyInjection\WebPushExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WebPushBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function getContainerExtension()
    {
        return new WebPushExtension();
    }
}
