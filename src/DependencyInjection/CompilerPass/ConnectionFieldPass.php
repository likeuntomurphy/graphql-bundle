<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\Argument\After;
use Likeuntomurphy\GraphQL\Argument\First;
use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
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
        foreach ($container->findTaggedServiceIds(GlobalObjectManagerInterface::TAG) as $serviceId => $_) {
            try {
                /** @var class-string<GlobalObjectManagerInterface> $managerClass */
                $managerClass = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

                $parentTypeName = new \ReflectionClass($managerClass::getManagedGlobalObject())->getShortName();

                foreach (new \ReflectionClass($managerClass)->getMethods() as $rm) {
                    $attrs = $rm->getAttributes(AsConnection::class);

                    if ([] === $attrs) {
                        continue;
                    }

                    $attr = $attrs[0]->newInstance();
                    $childTypeName = $this->resolveChildTypeName($rm);

                    $this->addConnectionField($parentTypeName, $childTypeName, $attr->fieldName, $rm->getName(), $serviceId, $container);
                }
            } catch (\Exception $e) {
                throw new InvalidConnectionFieldException($serviceId, $e);
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
