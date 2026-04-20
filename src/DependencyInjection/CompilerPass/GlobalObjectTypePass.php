<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Attribute\AsConnection;
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

        foreach ($container->findTaggedServiceIds(GlobalObjectManagerInterface::TAG) as $serviceId => $_) {
            try {
                /** @var class-string<GlobalObjectManagerInterface> $managerClass */
                $managerClass = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

                $rc = new \ReflectionClass($managerClass::getManagedGlobalObject());

                $typeName = $rc->getShortName();

                if (isset($classMap[$typeName])) {
                    throw new TypeNameCollisionException($typeName, $classMap[$typeName], $rc->getName());
                }

                $classMap[$typeName] = $rc->getName();

                $managerDefinition = $container->getDefinition($serviceId);
                $managerDefinition->addTag(GlobalObjectManagerInterface::TAG, ['key' => $rc->getShortName()]);

                foreach (self::NARROW_TAGS as $interface => $tag) {
                    if (is_subclass_of($managerClass, $interface)) {
                        $managerDefinition->addTag($tag, ['key' => $rc->getShortName()]);
                    }
                }

                $skipFields = $connectionFieldMap[$typeName] ?? [];

                $config = [
                    'name' => $rc->getShortName(),
                    'interfaces' => [new Reference(NodeInterface::class)],
                    'fields' => ['id' => new Reference(NodeId::class)] + $this->resolveObjectFields($rc, $container, $this->ensureLocalObjectType(...), skipId: true, skipFields: $skipFields),
                ];

                $definitions[TypeRegistry::TAG.'.'.$rc->getShortName()] = new Definition(ObjectType::class, [$config])
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['name' => $config['name']])
                ;
            } catch (\ReflectionException $e) {
                throw new InvalidGlobalObjectException($serviceId, $e);
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
     * Scans all managers for #[AsConnection] methods to build a map
     * of parent type name => list of field names handled by ConnectionFieldPass.
     *
     * @return array<string, list<string>>
     */
    private function buildConnectionFieldMap(ContainerBuilder $container): array
    {
        $map = [];

        foreach ($container->findTaggedServiceIds(GlobalObjectManagerInterface::TAG) as $serviceId => $_) {
            /** @var class-string<GlobalObjectManagerInterface> $managerClass */
            $managerClass = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

            $globalObjectClass = $managerClass::getManagedGlobalObject();

            // Skip unresolvable classes so the main loop can report them via InvalidGlobalObjectException.
            if (!class_exists($globalObjectClass)) {
                continue;
            }

            $parentTypeName = (new \ReflectionClass($globalObjectClass))->getShortName();

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
