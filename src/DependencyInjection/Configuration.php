<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dw_log');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('connection_name')
                    ->info('The Doctrine DBAL connection name to use')
                    ->defaultValue('default')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
