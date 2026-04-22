<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finalizes the stub ObjectType definitions that {@see GlobalObjectTypePass} left behind for
 * nested (non-global) classes, and recursively materializes any further stubs they imply.
 *
 * Reads: services tagged {@see TypeRegistry::TAG} whose tag carries `local => true` and `class`.
 * Writes: replaces each stub with a fully configured ObjectType; may register additional local
 * ObjectTypes discovered during recursive field resolution.
 *
 * Depends on {@see GlobalObjectTypePass} to have produced the stubs; running first is a silent
 * no-op because no tags carry the `local` marker yet.
 */
class LocalObjectTypePass implements CompilerPassInterface
{
    use TypeDefinitionTrait;

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(TypeRegistry::TAG) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                /** @var array<string, mixed> $attributes */
                if (empty($attributes['local']) || !isset($attributes['class'])) {
                    continue;
                }

                /** @var class-string $className */
                $className = $attributes['class'];
                $rc = new \ReflectionClass($className);

                $config = [
                    'name' => $rc->getShortName(),
                    'fields' => $this->resolveObjectFields($rc, $container, $this->resolveLocalObjectType(...)),
                ];

                $container->setDefinition($serviceId, new Definition(ObjectType::class, [$config])
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['class' => $className, 'name' => $config['name']]));
            }
        }
    }

    /** @param \ReflectionClass<object> $rc */
    private function resolveLocalObjectType(\ReflectionClass $rc, ContainerBuilder $container): Reference
    {
        $this->assertUniqueTypeName($rc, $container);

        $id = TypeRegistry::TAG.'.'.$rc->getShortName();

        if (!$container->has($id)) {
            // Register the definition before resolving fields so that circular
            // references find it in the container and return a Reference instead
            // of recursing infinitely.
            $definition = new Definition(ObjectType::class, [['name' => $rc->getShortName()]])
                ->setPublic(true)
                ->addTag(TypeRegistry::TAG, ['class' => $rc->getName(), 'name' => $rc->getShortName()])
            ;

            $container->addDefinitions([$id => $definition]);

            $config = [
                'name' => $rc->getShortName(),
                'fields' => $this->resolveObjectFields($rc, $container, $this->resolveLocalObjectType(...)),
            ];

            $definition->replaceArgument(0, $config);
        }

        return new Reference($id);
    }
}
