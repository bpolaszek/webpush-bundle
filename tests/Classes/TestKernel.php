<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use BenTools\WebPushBundle\WebPushBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    protected string $cacheDir;
    protected string $logDir;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        $uniqid = uniqid('webpush_test', true);
        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'cache';
        $this->logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'logs';
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new WebPushBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => getenv('APP_SECRET'),
        ]);
        $container->loadFromExtension('bentools_webpush', [
            'settings' => [
                'private_key' => 'this_is_a_private_key',
                'public_key' => 'this_is_a_public_key',
            ]
        ]);
        $loader->load(dirname(__DIR__) . '/Resources/services.yaml');
        $loader->load(dirname(__DIR__) . '/Resources/framework.yaml');
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }
}
