<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Attribute\Description;
use Likeuntomurphy\GraphQL\Attribute\Exclude;
use Likeuntomurphy\GraphQL\Attribute\Resolver;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

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

            if ($type->isIdentifiedBy(TypeIdentifier::OBJECT)) {
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
}
