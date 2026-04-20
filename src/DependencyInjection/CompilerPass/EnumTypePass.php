<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\PhpEnumType;
use Likeuntomurphy\GraphQL\Attribute\GlobalEnum;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
