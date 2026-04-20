<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\UnionType;
use Likeuntomurphy\GraphQL\Attribute\Exclude;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\Exception\InvalidMutationFieldException;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Type\Mutation;
use Likeuntomurphy\GraphQL\Type\NodeNotFound;
use Likeuntomurphy\GraphQL\Type\ValidationErrorList;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
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

        foreach ($container->findTaggedResourceIds(GlobalObject::RESOURCE_TAG) as $entityServiceId => $tags) {
            try {
                /** @var class-string $entityClass */
                $entityClass = $container->getDefinition($entityServiceId)->getClass() ?? $entityServiceId;
                $entityRc = new \ReflectionClass($entityClass);

                /** @var class-string $managerClass */
                $managerClass = $tags[0]['manager'];
                $typeName = $entityRc->getShortName();

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

                    $relations = [];
                    $payloadFields = 'delete' === $method ? [] : $this->resolveFields($entityRc, $container, $relations);

                    $args = match ($method) {
                        'create' => $payloadFields,
                        'delete' => $idArg,
                        default => $idArg + $payloadFields,
                    };

                    $handlerId = 'graphql.mutation.handler.'.$fieldName;
                    $definitions[$handlerId] = new Definition(MutationFieldHandler::class, [
                        $method,
                        $typeName,
                        $entityClass,
                        new Reference(MutationFieldResolver::class),
                        $relations,
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
                throw new InvalidMutationFieldException($entityServiceId, $e);
            }
        }

        $container->addDefinitions($definitions);
    }

    /**
     * @param \ReflectionClass<object>                               $rc
     * @param array<string, array{property: string, target: string}> &$relations keyed by GraphQL arg name
     *
     * @return array<string, array{type: Reference}>
     */
    private function resolveFields(\ReflectionClass $rc, ContainerBuilder $container, array &$relations): array
    {
        $fields = [];

        foreach ($rc->getProperties() as $rp) {
            if ($rp->isReadOnly() || 'id' === $rp->getName() || [] !== $rp->getAttributes(Exclude::class)) {
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

                if ([] !== $objectRc->getAttributes(GlobalObject::class)) {
                    $argName = $rp->getName().'Id';
                    $ref = new Reference(TypeRegistry::ID);
                    $fields[$argName] = [
                        'type' => $nullable ? $ref : $this->nonNull($ref, $container),
                    ];
                    $relations[$argName] = ['property' => $rp->getName(), 'target' => $objectRc->getShortName()];

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
            $relations = [];
            $container->addDefinitions([
                $id => new Definition(InputObjectType::class, [[
                    'name' => $name,
                    'fields' => $this->resolveFields($rc, $container, $relations),
                ]])->setPublic(true)->addTag(TypeRegistry::TAG, ['name' => $name]),
            ]);
        }

        return new Reference($id);
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
