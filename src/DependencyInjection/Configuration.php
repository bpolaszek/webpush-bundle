<?php

namespace BenTools\WebPushBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        if (Kernel::MAJOR_VERSION < 4) {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('bentools_webpush');
        } else {
            $treeBuilder = new TreeBuilder('bentools_webpush');
            $rootNode = $treeBuilder->getRootNode();
        }

        $rootNode
            ->children()

                ->arrayNode('settings')
                    ->children()
                        ->scalarNode('subject')
                        ->end()
                        ->scalarNode('public_key')
                        ->isRequired()
                        ->end()
                        ->scalarNode('private_key')
                        ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
