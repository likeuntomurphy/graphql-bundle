<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Seeds the container with GraphQL's built-in scalars (Int, Float, String, Boolean, ID).
 *
 * Reads: nothing.
 * Writes: `graphql.type.{Name}` services tagged {@see TypeRegistry::TAG} with a `name` attribute.
 * Consumed by: every later pass that references a scalar by name.
 */
class StandardTypePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definitions = [];

        foreach (Type::getStandardTypes() as $name => $instance) {
            $definition = new Definition($instance::class);
            $definition->setFactory([Type::class, strtolower($name)]);
            $definition->setPublic(true);
            $definition->addTag(TypeRegistry::TAG, ['name' => $name]);

            $id = TypeRegistry::TAG.".{$name}";
            $definitions[$id] = $definition;
        }

        $container->addDefinitions($definitions);
    }
}
