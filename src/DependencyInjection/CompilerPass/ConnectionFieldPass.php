<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\Argument\After;
use Likeuntomurphy\GraphQL\Argument\First;
use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;

class ConnectionFieldPass implements CompilerPassInterface
{
    use TypeDefinitionTrait;

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedResourceIds(GlobalObject::RESOURCE_TAG) as $entityServiceId => $tags) {
            try {
                /** @var class-string $entityClass */
                $entityClass = $container->getDefinition($entityServiceId)->getClass() ?? $entityServiceId;
                $entityRc = new \ReflectionClass($entityClass);

                /** @var class-string $managerClass */
                $managerClass = $tags[0]['manager'];
                $parentTypeName = $entityRc->getShortName();

                foreach (new \ReflectionClass($managerClass)->getMethods() as $rm) {
                    $connectionAttrs = $rm->getAttributes(AsConnection::class);

                    if ([] === $connectionAttrs) {
                        continue;
                    }

                    $connectionAttr = $connectionAttrs[0]->newInstance();
                    $childTypeName = $this->resolveChildTypeName($rm);

                    $this->addConnectionField($parentTypeName, $childTypeName, $connectionAttr->fieldName, $rm->getName(), $managerClass, $container);
                }
            } catch (\Exception $e) {
                throw new InvalidConnectionFieldException($entityServiceId, $e);
            }
        }
    }

    private function resolveChildTypeName(\ReflectionMethod $rm): string
    {
        $returnType = $this->typeResolver()->resolve($rm);

        if (!$returnType instanceof GenericType) {
            throw new \RuntimeException(\sprintf(
                '%s::%s() must have a generic return type (e.g. PaginatedResults<ChildType>).',
                $rm->getDeclaringClass()->getName(),
                $rm->getName(),
            ));
        }

        $variableTypes = $returnType->getVariableTypes();

        if ([] === $variableTypes || !$variableTypes[0] instanceof ObjectType) {
            throw new \RuntimeException(\sprintf(
                '%s::%s() generic return type must wrap an object type.',
                $rm->getDeclaringClass()->getName(),
                $rm->getName(),
            ));
        }

        /** @var class-string $className */
        $className = $variableTypes[0]->getClassName();

        return new \ReflectionClass($className)->getShortName();
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
                '%likeuntomurphy_graphql.pagination.limit%',
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
