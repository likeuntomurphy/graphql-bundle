<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\Argument\After;
use Likeuntomurphy\GraphQL\Argument\First;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds nested connection fields (e.g. `Project.attachments`) to existing parent ObjectTypes by
 * pairing each cross-global collection property with a `paginate{Property}` manager method.
 *
 * Reads: resources tagged {@see GlobalObject::RESOURCE_TAG}; parent types at
 * `graphql.type.{TypeName}`; child `{ChildName}Connection` types.
 * Writes: mutates the parent ObjectType's `fields` config in place and registers
 * `graphql.connection.handler.{Parent}.{field}` handler services.
 *
 * Depends on {@see GlobalObjectTypePass} (parent ObjectType must exist and be mutable) and
 * {@see QueryFieldPass} (Connection child types must already be registered).
 */
class ConnectionFieldPass implements CompilerPassInterface
{
    use TypeDefinitionTrait;

    public function process(ContainerBuilder $container): void
    {
        $entities = $this->discoverEntities($container);
        $globalClasses = array_keys($entities);

        foreach ($entities as $entityClass => [$entityRc, $managerClass]) {
            try {
                $parentTypeName = $entityRc->getShortName();
                $managerRc = new \ReflectionClass($managerClass);

                foreach ($this->connectionProperties($entityRc, $globalClasses) as $propertyName => $childClass) {
                    $childRc = new \ReflectionClass($childClass);
                    $childTypeName = $childRc->getShortName();
                    $methodName = 'paginate'.ucfirst($propertyName);

                    if (!$managerRc->hasMethod($methodName)) {
                        throw new \RuntimeException(\sprintf(
                            '%s::$%s is a collection of %s but %s has no method %s(). Expected signature: public function %s(%s $parent, CursorPaginationParams $params): PaginatedResults<%s>.',
                            $entityClass,
                            $propertyName,
                            $childTypeName,
                            $managerClass,
                            $methodName,
                            $methodName,
                            $parentTypeName,
                            $childTypeName,
                        ));
                    }

                    $this->addConnectionField(
                        $parentTypeName,
                        $childTypeName,
                        $propertyName,
                        $methodName,
                        $managerClass,
                        $container,
                    );
                }
            } catch (\Exception $e) {
                throw new InvalidConnectionFieldException($entityClass, $e);
            }
        }
    }

    /**
     * @return array<class-string, array{\ReflectionClass<object>, class-string}>
     */
    private function discoverEntities(ContainerBuilder $container): array
    {
        $entities = [];

        foreach ($container->findTaggedResourceIds(GlobalObject::RESOURCE_TAG) as $id => $tags) {
            /** @var class-string $entityClass */
            $entityClass = $container->getDefinition($id)->getClass() ?? $id;

            if (!class_exists($entityClass)) {
                continue;
            }

            /** @var class-string $managerClass */
            $managerClass = $tags[0]['manager'];

            $entities[$entityClass] = [new \ReflectionClass($entityClass), $managerClass];
        }

        return $entities;
    }

    private function addConnectionField(
        string $parentTypeName,
        string $childTypeName,
        string $fieldName,
        string $method,
        string $managerServiceId,
        ContainerBuilder $container,
    ): void {
        $parentTypeId = TypeRegistry::TAG.'.'.$parentTypeName;
        $connectionTypeId = TypeRegistry::TAG.'.'.$childTypeName.'Connection';

        $handlerId = 'graphql.connection.handler.'.$parentTypeName.'.'.$fieldName;
        $container->setDefinition(
            $handlerId,
            new Definition(NestedConnectionFieldHandler::class, [
                (new Definition('Closure'))
                    ->setFactory([\Closure::class, 'fromCallable'])
                    ->setArguments([[new Reference($managerServiceId), $method]]),
                new Reference(ConnectionResolver::class),
            ]),
        );

        $parentDefinition = $container->findDefinition($parentTypeId);

        /** @var array{fields: array<string, mixed>} $config */
        $config = $parentDefinition->getArgument(0);
        $config['fields'][$fieldName] = [
            'type' => new Reference($connectionTypeId),
            'args' => [
                'first' => First::CONFIG,
                'after' => After::CONFIG,
            ],
            'resolve' => new Reference($handlerId),
        ];
        $parentDefinition->replaceArgument(0, $config);
    }
}
