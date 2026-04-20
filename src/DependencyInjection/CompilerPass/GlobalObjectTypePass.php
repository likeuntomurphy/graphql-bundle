<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\Exception\InvalidGlobalObjectException;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Field\NodeId;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use Likeuntomurphy\GraphQL\Model\ValidationError;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GlobalObjectTypePass implements CompilerPassInterface
{
    use TypeDefinitionTrait;

    public const string CREATABLE_MANAGER_TAG = GlobalObjectManagerInterface::TAG.'.creatable';
    public const string UPDATABLE_MANAGER_TAG = GlobalObjectManagerInterface::TAG.'.updatable';
    public const string DELETABLE_MANAGER_TAG = GlobalObjectManagerInterface::TAG.'.deletable';

    /** @var array<class-string, string> */
    private const array NARROW_TAGS = [
        CreatableManagerInterface::class => self::CREATABLE_MANAGER_TAG,
        UpdatableManagerInterface::class => self::UPDATABLE_MANAGER_TAG,
        DeletableManagerInterface::class => self::DELETABLE_MANAGER_TAG,
    ];

    public function process(ContainerBuilder $container): void
    {
        $definitions = [];
        $classMap = [];
        $connectionFieldMap = $this->buildConnectionFieldMap($container);

        foreach ($this->discoverEntities($container) as $entityClass => [$entityRc, $managerClass]) {
            try {
                $typeName = $entityRc->getShortName();

                if (isset($classMap[$typeName])) {
                    throw new TypeNameCollisionException($typeName, $classMap[$typeName], $entityClass);
                }

                $classMap[$typeName] = $entityClass;

                $managerDefinition = $container->findDefinition($managerClass);
                $managerDefinition->addTag(GlobalObjectManagerInterface::TAG, ['key' => $typeName]);

                foreach (self::NARROW_TAGS as $interface => $tag) {
                    if (is_subclass_of($managerClass, $interface)) {
                        $managerDefinition->addTag($tag, ['key' => $typeName]);
                    }
                }

                $skipFields = $connectionFieldMap[$typeName] ?? [];

                $config = [
                    'name' => $typeName,
                    'interfaces' => [new Reference(NodeInterface::class)],
                    'fields' => ['id' => new Reference(NodeId::class)] + $this->resolveObjectFields($entityRc, $container, $this->ensureLocalObjectType(...), skipId: true, skipFields: $skipFields),
                ];

                $definitions[TypeRegistry::TAG.'.'.$typeName] = new Definition(ObjectType::class, [$config])
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['name' => $typeName])
                ;
            } catch (\ReflectionException $e) {
                throw new InvalidGlobalObjectException($entityClass, $e);
            }
        }

        $container->addDefinitions($definitions);

        // Build class → type name map for ObjectTypeResolver.
        $container->setParameter('likeuntomurphy_graphql.type_class_map', array_flip($classMap) + [
            ValidationErrorList::class => 'ValidationErrorList',
            ValidationError::class => 'ValidationError',
            NodeNotFound::class => 'NodeNotFound',
        ]);
    }

    /**
     * Iterate resource-tagged entities, yielding [ReflectionClass, managerClass] per entity class.
     *
     * @return iterable<string, array{\ReflectionClass<object>, class-string}>
     */
    private function discoverEntities(ContainerBuilder $container): iterable
    {
        foreach ($container->findTaggedResourceIds(GlobalObject::RESOURCE_TAG) as $id => $tags) {
            $entityClass = $container->getDefinition($id)->getClass() ?? $id;

            if (!class_exists($entityClass)) {
                throw new InvalidGlobalObjectException(
                    $entityClass,
                    new \ReflectionException(\sprintf('Class "%s" does not exist', $entityClass)),
                );
            }

            /** @var class-string $managerClass */
            $managerClass = $tags[0]['manager'];

            yield $entityClass => [new \ReflectionClass($entityClass), $managerClass];
        }
    }

    /**
     * Scans all managers for #[AsConnection] methods to build a map
     * of parent type name => list of field names handled by ConnectionFieldPass.
     *
     * @return array<string, list<string>>
     */
    private function buildConnectionFieldMap(ContainerBuilder $container): array
    {
        $map = [];

        foreach ($this->discoverEntities($container) as [$entityRc, $managerClass]) {
            $parentTypeName = $entityRc->getShortName();

            foreach (new \ReflectionClass($managerClass)->getMethods() as $rm) {
                $attrs = $rm->getAttributes(AsConnection::class);

                if ([] === $attrs) {
                    continue;
                }

                $attr = $attrs[0]->newInstance();
                $map[$parentTypeName][] = $attr->fieldName;
            }
        }

        return $map;
    }

    /** @param \ReflectionClass<object> $rc */
    private function ensureLocalObjectType(\ReflectionClass $rc, ContainerBuilder $container): Reference
    {
        $this->assertUniqueTypeName($rc, $container);

        $id = TypeRegistry::TAG.'.'.$rc->getShortName();

        if (!$container->has($id)) {
            $container->addDefinitions([
                $id => new Definition(ObjectType::class)
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['class' => $rc->getName(), 'name' => $rc->getShortName(), 'local' => true]),
            ]);
        }

        return new Reference($id);
    }
}
