<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GraphQLExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{pagination: array{limit: int}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('likeuntomurphy_graphql.pagination.limit', $config['pagination']['limit']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'serializer' => ['enabled' => true],
        ]);
    }
}
