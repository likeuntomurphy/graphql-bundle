<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Attribute\Description;
use Likeuntomurphy\GraphQL\Attribute\Exclude;
use Likeuntomurphy\GraphQL\Attribute\Resolver;
use Likeuntomurphy\GraphQL\Attribute\Type as TypeAttribute;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * Shared reflection → GraphQL type-graph translation used by the object/field compiler passes.
 *
 * Two patterns live here that are worth calling out:
 *
 * 1. **Stub-and-finalize.** When a pass encounters a nested class it has not seen before, it
 *    registers a placeholder {@see Definition} tagged with a `local => true` or `enum => true`
 *    marker (see {@see GlobalObjectTypePass::ensureLocalObjectType()} and
 *    {@see self::ensureEnumType()}). A later pass finds the marker and replaces the stub with
 *    the fully configured type. This lets earlier passes emit references without needing to
 *    fully resolve the downstream type graph — the stub makes the service ID valid so
 *    {@see Reference} resolves, and finalization happens in topological order.
 *
 * 2. **Idempotent registration.** Every `ensure*` / `resolve*` helper first checks whether
 *    the target service ID is already present; this keeps recursive traversal of the reflected
 *    type graph safe (cycles return the existing {@see Reference} instead of recursing).
 *
 * Field resolution runs callbacks supplied by the calling pass (see the `$objectHandler`
 * parameter of {@see self::resolveObjectFields()}) so the same traversal logic produces stubs
 * in one pass and fully configured types in another.
 */
trait TypeDefinitionTrait
{
    private const array SCALAR_TYPE_MAP = [
        TypeIdentifier::INT->value => TypeRegistry::INT,
        TypeIdentifier::FLOAT->value => TypeRegistry::FLOAT,
        TypeIdentifier::STRING->value => TypeRegistry::STRING,
        TypeIdentifier::BOOL->value => TypeRegistry::BOOLEAN,
    ];

    private ?TypeResolver $typeResolver = null;

    protected function typeResolver(): TypeResolver
    {
        return $this->typeResolver ??= TypeResolver::create();
    }

    protected function nonNull(Reference $reference, ContainerBuilder $container): Reference
    {
        if (!$container->has($id = $reference.'.'.TypeRegistry::NON_NULL_SUFFIX)) {
            $container->addDefinitions([$id => new Definition(NonNull::class, [$reference])]);
        }

        return new Reference($id);
    }

    protected function listOf(Reference $reference, ContainerBuilder $container): Reference
    {
        if (!$container->has($id = $reference.'.'.TypeRegistry::LIST_OF_SUFFIX)) {
            $container->addDefinitions([$id => new Definition(ListOfType::class, [$reference])]);
        }

        return new Reference($id);
    }

    protected function resolveTypeReference(TypeInfoType $type, string $propertyName): Reference
    {
        foreach (self::SCALAR_TYPE_MAP as $identifier => $serviceId) {
            if ($type->isIdentifiedBy(TypeIdentifier::from($identifier))) {
                return new Reference($serviceId);
            }
        }

        if ($type->isIdentifiedBy(TypeIdentifier::OBJECT) && class_exists((string) $type)) {
            return new Reference(TypeRegistry::TAG.'.'.new \ReflectionClass((string) $type)->getShortName());
        }

        throw new UnsupportedTypeException($type, $propertyName);
    }

    /**
     * @param array<string, mixed> $fieldConfig
     */
    protected function applyPropertyAttributes(\ReflectionProperty $rp, array &$fieldConfig): void
    {
        if ($attr = $rp->getAttributes(Description::class)[0] ?? null) {
            $fieldConfig['description'] = $attr->newInstance()->description;
        }

        if ($attr = $rp->getAttributes(Resolver::class)[0] ?? null) {
            $fieldConfig['resolve'] = new Reference($attr->newInstance()->resolver);
        }
    }

    /**
     * Identify properties on $rc that are collections of global-object classes.
     *
     * @param \ReflectionClass<object> $rc
     * @param list<class-string>       $globalClasses
     *
     * @return array<string, class-string> map of property name → element class-string
     */
    protected function connectionProperties(\ReflectionClass $rc, array $globalClasses): array
    {
        $connections = [];

        foreach ($rc->getProperties() as $rp) {
            $type = $this->typeResolver()->resolve($rp);

            if ($type instanceof NullableType) {
                $type = $type->getWrappedType();
            }

            $element = $this->collectionElementType($type);

            if (null === $element || !$element->isIdentifiedBy(TypeIdentifier::OBJECT)) {
                continue;
            }

            $className = (string) $element;

            if (!\in_array($className, $globalClasses, true)) {
                continue;
            }

            /** @var class-string $className */
            $connections[$rp->getName()] = $className;
        }

        return $connections;
    }

    /** @param \ReflectionClass<object> $rc */
    protected function assertUniqueTypeName(\ReflectionClass $rc, ContainerBuilder $container): void
    {
        $id = TypeRegistry::TAG.'.'.$rc->getShortName();

        if (!$container->has($id)) {
            return;
        }

        $tags = $container->findDefinition($id)->getTag(TypeRegistry::TAG);

        /** @var array<string, mixed> $firstTag */
        $firstTag = $tags[0] ?? [];

        /** @var null|string $existingClass */
        $existingClass = $firstTag['class'] ?? null;

        if (null !== $existingClass && $existingClass !== $rc->getName()) {
            throw new TypeNameCollisionException($rc->getShortName(), $existingClass, $rc->getName());
        }
    }

    /** @param \ReflectionClass<object> $rc */
    protected function ensureEnumType(\ReflectionClass $rc, ContainerBuilder $container): Reference
    {
        $this->assertUniqueTypeName($rc, $container);

        $id = TypeRegistry::TAG.'.'.$rc->getShortName();

        if (!$container->has($id)) {
            $container->addDefinitions([
                $id => new Definition(ObjectType::class)
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['class' => $rc->getName(), 'name' => $rc->getShortName(), 'enum' => true]),
            ]);
        }

        return new Reference($id);
    }

    /**
     * @param \ReflectionClass<object>                                        $rc
     * @param callable(\ReflectionClass<object>, ContainerBuilder): Reference $objectHandler
     * @param list<string>                                                    $skipFields    Property names to skip (e.g. connection fields handled elsewhere).
     *
     * @return array<string, array<string, mixed>>
     */
    protected function resolveObjectFields(
        \ReflectionClass $rc,
        ContainerBuilder $container,
        callable $objectHandler,
        bool $skipId = false,
        array $skipFields = [],
    ): array {
        $fields = [];

        foreach ($rc->getProperties() as $rp) {
            if (isset($rp->getAttributes(Exclude::class)[0]) || ($skipId && 'id' === $rp->getName())) {
                continue;
            }

            if (\in_array($rp->getName(), $skipFields, true)) {
                continue;
            }

            $type = $this->typeResolver()->resolve($rp);

            if ($nullable = $type instanceof NullableType) {
                $type = $type->getWrappedType();
            }

            if ($typeAttr = $rp->getAttributes(TypeAttribute::class)[0] ?? null) {
                $ref = new Reference(TypeRegistry::TAG.'.'.$typeAttr->newInstance()->name);
            } elseif ($type->isIdentifiedBy(TypeIdentifier::OBJECT)) {
                /** @var class-string $className */
                $className = (string) $type;
                $objectRc = new \ReflectionClass($className);

                $ref = $objectRc->isEnum()
                    ? $this->ensureEnumType($objectRc, $container)
                    : $objectHandler($objectRc, $container);
            } else {
                $ref = $this->resolveTypeReference($type, $rp->getName());
            }

            $fieldConfig = ['type' => $nullable ? $ref : $this->nonNull($ref, $container)];
            $this->applyPropertyAttributes($rp, $fieldConfig);
            $fields[$rp->getName()] = $fieldConfig;
        }

        return $fields;
    }

    private function collectionElementType(TypeInfoType $type): ?TypeInfoType
    {
        if ($type instanceof CollectionType) {
            return $type->getCollectionValueType();
        }

        if ($type instanceof GenericType) {
            $vars = $type->getVariableTypes();

            return $vars[\count($vars) - 1] ?? null;
        }

        return null;
    }
}
