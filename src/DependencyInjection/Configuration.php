<?php

namespace MartenaSoft\ConsoleProfileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('console_profile');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('enabled')
            ->defaultTrue()
            ->end()
            ->arrayNode('collectors')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->info('Limits Symfony profiler data collectors by their collector id, e.g. request, time, memory, events, logger, doctrine, command.')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
