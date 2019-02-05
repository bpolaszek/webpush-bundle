<?php

namespace BenTools\WebPushBundle\Tests\Classes;

use BenTools\WebPushBundle\WebPushBundle;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    protected $cacheDir;
    protected $logDir;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        $uniqid = uniqid('webpush_test', true);
        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'cache';
        $this->logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'logs';
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new WebPushBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('framework', [
            'secret' => getenv('APP_SECRET'),
        ]);
        $c->loadFromExtension('bentools_webpush', [
            'settings' => [
                'private_key' => 'this_is_a_private_key',
                'public_key' => 'this_is_a_public_key',
            ]
        ]);
        $loader->load(dirname(__DIR__) . '/Resources/services.yaml');

        if (1 === version_compare(self::VERSION, '4.0')) {
            $loader->load(dirname(__DIR__) . '/Resources/framework.yaml');
        }

        $c->addCompilerPass(new PublicServicePass());
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    public function getLogDir()
    {
        return $this->logDir;
    }
}
