<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\PhpEnumType;
use Likeuntomurphy\GraphQL\Attribute\GlobalEnum;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Materializes enum types: finalizes stubs other passes left behind with `enum => true` tags,
 * and registers any `#[GlobalEnum]`-tagged enums not yet referenced by a field.
 *
 * Reads: services tagged {@see TypeRegistry::TAG} with `enum => true`; resources tagged
 * {@see GlobalEnum::RESOURCE_TAG}.
 * Writes: replaces stubs with {@see PhpEnumType} definitions and registers any missing
 * global enums at `graphql.type.{Name}`.
 *
 * Runs before {@see MutationFieldPass}; that pass has its own fallback path
 * ({@see MutationFieldPass::ensureEnumTypeResolved()}) for enums first discovered through
 * mutation input and therefore never stubbed.
 */
class EnumTypePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(TypeRegistry::TAG) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                /** @var array<string, mixed> $attributes */
                if (empty($attributes['enum']) || !isset($attributes['class'])) {
                    continue;
                }

                $container->setDefinition(
                    $serviceId,
                    new Definition(PhpEnumType::class, [$attributes['class']])
                        ->setPublic(true)
                        ->addTag(TypeRegistry::TAG, ['name' => $attributes['name']]),
                );
            }
        }

        foreach ($container->findTaggedResourceIds(GlobalEnum::RESOURCE_TAG) as $id => $_) {
            /** @var class-string $enumClass */
            $enumClass = $container->getDefinition($id)->getClass() ?? $id;
            $name = new \ReflectionClass($enumClass)->getShortName();
            $typeId = TypeRegistry::TAG.'.'.$name;

            if ($container->has($typeId)) {
                continue;
            }

            $container->setDefinition(
                $typeId,
                new Definition(PhpEnumType::class, [$enumClass])
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['name' => $name]),
            );
        }
    }
}
