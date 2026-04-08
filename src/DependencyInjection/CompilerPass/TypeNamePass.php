<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\Attribute\Name;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\AutoconfigureFailedException;

class TypeNamePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(TypeRegistry::TAG) as $id => $tags) {
            /** @var array<string, mixed> $firstTag */
            $firstTag = $tags[0] ?? [];
            if (!isset($firstTag['name'])) {
                if (!class_exists($id)) {
                    throw new AutoconfigureFailedException(sprintf('Service "%s" is tagged with "%s" but does not exist as a class.', $id, TypeRegistry::TAG));
                }

                $rc = new \ReflectionClass($id);
                $name = $rc->getShortName();

                if ($attr = $rc->getAttributes(Name::class)[0] ?? null) {
                    $name = $attr->newInstance()->name;
                }

                $definition = $container->getDefinition($id);
                $definition->clearTag(TypeRegistry::TAG);
                $definition->addTag(TypeRegistry::TAG, compact('name'));
            }
        }
    }
}
