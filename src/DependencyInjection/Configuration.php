<?php

namespace BenTools\WebPushBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bentools_webpush');
        $rootNode = $treeBuilder->getRootNode();

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
