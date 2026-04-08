<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\UnionType;
use Likeuntomurphy\GraphQL\Attribute\IdField;
use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\Exception\InvalidMutationFieldException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Type\Mutation;
use Likeuntomurphy\GraphQL\Type\NodeNotFound;
use Likeuntomurphy\GraphQL\Type\ValidationErrorList;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
use Likeuntomurphy\GraphQL\ValidatableManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\TypeIdentifier;

use function Symfony\Component\String\s;

class MutationFieldPass implements CompilerPassInterface
{
    use TypeDefinitionTrait;

    /** @var array<string, class-string> */
    private const array METHODS = [
        'create' => CreatableManagerInterface::class,
        'update' => UpdatableManagerInterface::class,
        'delete' => DeletableManagerInterface::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        $definitions = [];

        foreach ($container->findTaggedServiceIds(GlobalObjectManagerInterface::TAG) as $serviceId => $_) {
            try {
                /** @var class-string<GlobalObjectManagerInterface> $managerClass */
                $managerClass = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

                $objectClass = $managerClass::getManagedGlobalObject();
                $typeName = new \ReflectionClass($objectClass)->getShortName();

                $unionName = $typeName.'MutationResult';
                $unionId = TypeRegistry::TAG.'.'.$unionName;

                $hasMethod = false;

                foreach (self::METHODS as $method => $interface) {
                    if (!is_subclass_of($managerClass, $interface)) {
                        continue;
                    }

                    $hasMethod = true;

                    $fieldName = s($method.$typeName)->camel()->toString();
                    $idArg = ['id' => ['type' => $this->nonNull(new Reference(TypeRegistry::ID), $container)]];

                    $dtoClass = $managerClass::getManagedDataTransferObject();

                    $args = match ($method) {
                        'create' => $this->resolveFields(new \ReflectionClass($dtoClass), $container),
                        'delete' => $idArg,
                        default => $idArg + $this->resolveFields(new \ReflectionClass($dtoClass), $container),
                    };

                    if ('delete' !== $method && is_subclass_of($managerClass, ValidatableManagerInterface::class)) {
                        $enumClass = $managerClass::getValidationGroupEnum();

                        /** @var \ReflectionClass<object> $rc */
                        $rc = new \ReflectionClass($enumClass);
                        $ref = $this->ensureEnumTypeResolved($rc, $container);
                        $args['validationGroups'] = ['type' => $this->listOf($this->nonNull($ref, $container), $container)];
                    }

                    $idFields = 'delete' !== $method ? $this->collectIdFields($dtoClass) : [];

                    $handlerId = 'graphql.mutation.handler.'.$fieldName;
                    $definitions[$handlerId] = new Definition(MutationFieldHandler::class, [
                        $method,
                        $typeName,
                        new Reference(MutationFieldResolver::class),
                        $idFields,
                    ]);

                    $config = [
                        'name' => $fieldName,
                        'type' => new Reference($unionId),
                        'args' => $args,
                        'resolve' => new Reference($handlerId),
                    ];

                    $definitions['graphql.mutation.field.'.$fieldName] = new Definition(FieldDefinition::class, [$config])->addTag(Mutation::FIELD_TAG);
                }

                if ($hasMethod) {
                    $definitions[$unionId] = new Definition(UnionType::class, [[
                        'name' => $unionName,
                        'types' => [
                            new Reference(TypeRegistry::TAG.'.'.$typeName),
                            new Reference(ValidationErrorList::class),
                            new Reference(NodeNotFound::class),
                        ],
                        'resolveType' => new Reference(ObjectTypeResolver::class),
                    ]])->setPublic(true)->addTag(TypeRegistry::TAG, ['name' => $unionName]);
                }
            } catch (\ReflectionException $e) {
                throw new InvalidMutationFieldException($serviceId, $e);
            }
        }

        $container->addDefinitions($definitions);
    }

    /**
     * @param \ReflectionClass<object> $rc
     *
     * @return array<string, array{type: Reference}>
     */
    private function resolveFields(\ReflectionClass $rc, ContainerBuilder $container): array
    {
        $fields = [];

        foreach ($rc->getProperties() as $rp) {
            if ($rp->isReadOnly()) {
                continue;
            }

            if ([] !== $rp->getAttributes(IdField::class)) {
                $ref = new Reference(TypeRegistry::ID);
                $type = $this->typeResolver()->resolve($rp);
                $nullable = $type instanceof NullableType;
                $fields[$rp->getName()] = [
                    'type' => $nullable ? $ref : $this->nonNull($ref, $container),
                ];

                continue;
            }

            $type = $this->typeResolver()->resolve($rp);

            $nullable = $type instanceof NullableType;
            if ($nullable) {
                $type = $type->getWrappedType();
            }

            if ($type->isIdentifiedBy(TypeIdentifier::OBJECT)) {
                /** @var class-string $className */
                $className = (string) $type;
                $objectRc = new \ReflectionClass($className);

                if ($objectRc->isEnum()) {
                    $ref = $this->ensureEnumTypeResolved($objectRc, $container);
                    $fields[$rp->getName()] = [
                        'type' => $nullable ? $ref : $this->nonNull($ref, $container),
                    ];

                    continue;
                }

                $ref = $this->resolveInputObjectType($objectRc, $container);
                $fields[$rp->getName()] = [
                    'type' => $nullable ? $ref : $this->nonNull($ref, $container),
                ];

                continue;
            }

            $ref = $this->resolveTypeReference($type, $rp->getName());

            $fields[$rp->getName()] = [
                'type' => $nullable ? $ref : $this->nonNull($ref, $container),
            ];
        }

        return $fields;
    }

    /**
     * @param \ReflectionClass<object> $rc
     */
    private function resolveInputObjectType(\ReflectionClass $rc, ContainerBuilder $container): Reference
    {
        $name = $rc->getShortName().'Input';
        $id = TypeRegistry::TAG.'.'.$name;

        if (!$container->has($id)) {
            $container->addDefinitions([
                $id => new Definition(InputObjectType::class, [[
                    'name' => $name,
                    'fields' => $this->resolveFields($rc, $container),
                ]])->setPublic(true)->addTag(TypeRegistry::TAG, ['name' => $name]),
            ]);
        }

        return new Reference($id);
    }

    /**
     * @param class-string $dtoClass
     *
     * @return list<string>
     */
    private function collectIdFields(string $dtoClass): array
    {
        $fields = [];

        foreach (new \ReflectionClass($dtoClass)->getProperties() as $rp) {
            if ([] !== $rp->getAttributes(IdField::class)) {
                $fields[] = $rp->getName();
            }
        }

        return $fields;
    }

    /**
     * Register an enum directly as PhpEnumType since EnumTypePass has already run.
     *
     * @param \ReflectionClass<object> $rc
     */
    private function ensureEnumTypeResolved(\ReflectionClass $rc, ContainerBuilder $container): Reference
    {
        $this->assertUniqueTypeName($rc, $container);

        $id = TypeRegistry::TAG.'.'.$rc->getShortName();

        if (!$container->has($id)) {
            $container->addDefinitions([
                $id => new Definition(PhpEnumType::class, [$rc->getName()])
                    ->setPublic(true)
                    ->addTag(TypeRegistry::TAG, ['name' => $rc->getShortName()]),
            ]);
        }

        return new Reference($id);
    }
}
