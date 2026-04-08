<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection;

use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('likeuntomurphy_graphql');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('pagination')
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('limit')
            ->defaultValue(CursorPaginationParams::LIMIT)
            ->min(1)
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
