<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Argument\After;
use Likeuntomurphy\GraphQL\Argument\First;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\Exception\InvalidQueryFieldException;
use Likeuntomurphy\GraphQL\Field\Cursor;
use Likeuntomurphy\GraphQL\Field\PageInfo as PageInfoField;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Type\ConnectionInterface;
use Likeuntomurphy\GraphQL\Type\EdgeInterface;
use Likeuntomurphy\GraphQL\Type\Query;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\String\Inflector\EnglishInflector;

use function Symfony\Component\String\s;

/**
 * Generates root-level query fields: Relay Edge/Connection types for every global object, plus
 * a pluralized `things` query field when the manager implements {@see ListableManagerInterface}.
 *
 * Reads: resources tagged {@see GlobalObject::RESOURCE_TAG}.
 * Writes:
 *  - `graphql.type.{TypeName}Edge` and `graphql.type.{TypeName}Connection` ObjectTypes.
 *  - `graphql.query.field.{fieldName}` FieldDefinitions tagged {@see Query::FIELD_TAG}.
 *
 * Depends on {@see GlobalObjectTypePass} — the Edge's `node` field references the parent
 * `graphql.type.{TypeName}` service which that pass creates.
 * Consumed by: {@see ConnectionFieldPass}, which wires nested connection fields pointing at
 * the `{TypeName}Connection` services registered here.
 */
class QueryFieldPass implements CompilerPassInterface
{
    private EnglishInflector $inflector;

    public function __construct()
    {
        $this->inflector = new EnglishInflector();
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedResourceIds(GlobalObject::RESOURCE_TAG) as $entityServiceId => $tags) {
            try {
                /** @var class-string $entityClass */
                $entityClass = $container->getDefinition($entityServiceId)->getClass() ?? $entityServiceId;
                $entityRc = new \ReflectionClass($entityClass);

                /** @var class-string $managerClass */
                $managerClass = $tags[0]['manager'];
                $typeName = $entityRc->getShortName();

                $this->createConnectionTypes($typeName, $container);

                if (is_subclass_of($managerClass, ListableManagerInterface::class)) {
                    $this->createQueryField($typeName, $managerClass, $container);
                }
            } catch (\ReflectionException $e) {
                throw new InvalidQueryFieldException($entityServiceId, $e);
            }
        }
    }

    private function createConnectionTypes(string $typeName, ContainerBuilder $container): void
    {
        $edgeId = TypeRegistry::TAG.'.'.$typeName.'Edge';
        $connectionId = TypeRegistry::TAG.'.'.$typeName.'Connection';

        // Edge type
        $edgeConfig = [
            'name' => $typeName.'Edge',
            'interfaces' => [new Reference(EdgeInterface::class)],
            'fields' => [
                'node' => [
                    'type' => new Reference(TypeRegistry::TAG.'.'.$typeName),
                    'description' => EdgeInterface::NODE_FIELD_DESCRIPTION,
                ],
                'cursor' => new Reference(Cursor::class),
            ],
        ];

        $container->setDefinition(
            $edgeId,
            new Definition(ObjectType::class, [$edgeConfig])
                ->setPublic(true)
                ->addTag(TypeRegistry::TAG, ['name' => $typeName.'Edge']),
        );

        // List of edges
        $edgeListId = $edgeId.'.list_of';

        if (!$container->has($edgeListId)) {
            $container->setDefinition(
                $edgeListId,
                new Definition(ListOfType::class, [new Reference($edgeId)]),
            );
        }

        // Connection type
        $connectionConfig = [
            'name' => $typeName.'Connection',
            'interfaces' => [new Reference(ConnectionInterface::class)],
            'fields' => [
                'edges' => [
                    'type' => new Reference($edgeListId),
                    'description' => ConnectionInterface::EDGES_FIELD_DESCRIPTION,
                ],
                'pageInfo' => new Reference(PageInfoField::class),
            ],
        ];

        $container->setDefinition(
            $connectionId,
            new Definition(ObjectType::class, [$connectionConfig])
                ->setPublic(true)
                ->addTag(TypeRegistry::TAG, ['name' => $typeName.'Connection']),
        );
    }

    private function createQueryField(string $typeName, string $managerServiceId, ContainerBuilder $container): void
    {
        $fieldName = s($this->inflector->pluralize($typeName)[0])->camel()->toString();

        $handlerId = 'graphql.connection.handler.Query.'.$fieldName;
        $container->setDefinition(
            $handlerId,
            new Definition(ConnectionFieldHandler::class, [
                new Reference($managerServiceId),
                new Reference(ConnectionResolver::class),
            ]),
        );

        $config = [
            'name' => $fieldName,
            'type' => new Reference(TypeRegistry::TAG.'.'.$typeName.'Connection'),
            'args' => [
                'first' => First::CONFIG,
                'after' => After::CONFIG,
            ],
            'resolve' => new Reference($handlerId),
        ];

        $container->setDefinition(
            'graphql.query.field.'.$fieldName,
            new Definition(FieldDefinition::class, [$config])
                ->addTag(Query::FIELD_TAG),
        );
    }
}
