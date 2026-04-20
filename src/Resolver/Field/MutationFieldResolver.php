<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass;
use Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
use Likeuntomurphy\GraphQL\ValidationGroupsAwareInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

class MutationFieldResolver
{
    /**
     * @param ServiceProviderInterface<CreatableManagerInterface>    $creatableManagers
     * @param ServiceProviderInterface<UpdatableManagerInterface>    $updatableManagers
     * @param ServiceProviderInterface<DeletableManagerInterface>    $deletableManagers
     * @param ServiceProviderInterface<GlobalObjectManagerInterface> $managers
     */
    public function __construct(
        private NodeIdResolver $nodeIdResolver,
        private DenormalizerInterface $denormalizer,
        private ValidatorInterface $validator,
        #[AutowireLocator(GlobalObjectTypePass::CREATABLE_MANAGER_TAG, indexAttribute: 'key')] private ServiceProviderInterface $creatableManagers,
        #[AutowireLocator(GlobalObjectTypePass::UPDATABLE_MANAGER_TAG, indexAttribute: 'key')] private ServiceProviderInterface $updatableManagers,
        #[AutowireLocator(GlobalObjectTypePass::DELETABLE_MANAGER_TAG, indexAttribute: 'key')] private ServiceProviderInterface $deletableManagers,
        #[AutowireLocator(GlobalObjectManagerInterface::TAG, indexAttribute: 'key')] private ServiceProviderInterface $managers,
    ) {
    }

    /**
     * @param class-string                                           $entityClass
     * @param array<string, mixed>                                   $args
     * @param array<string, array{property: string, target: string}> $relations
     */
    public function resolve(string $method, string $typeName, string $entityClass, array $args, array $relations = []): object
    {
        $args = $this->flattenEnums($args);

        return match ($method) {
            'create' => $this->handleCreate($this->creatableManagers->get($typeName), $entityClass, $args, $relations),
            'update' => $this->handleUpdate($this->updatableManagers->get($typeName), $entityClass, $args, $relations),
            'delete' => $this->handleDelete($this->deletableManagers->get($typeName), $args),
            default => throw new UnknownMutationMethodException($method),
        };
    }

    /**
     * @param class-string                                           $entityClass
     * @param array<string, mixed>                                   $args
     * @param array<string, array{property: string, target: string}> $relations
     */
    private function handleCreate(CreatableManagerInterface $manager, string $entityClass, array $args, array $relations): object
    {
        [$args, $violations] = $this->resolveRelations($args, $relations);

        if ($violations->count() > 0) {
            return ValidationErrorList::fromConstraintViolationList($violations);
        }

        /** @var object $document */
        $document = $this->denormalizer->denormalize($args, $entityClass, context: [AbstractNormalizer::OBJECT_TO_POPULATE => new $entityClass()]);

        $groups = $manager instanceof ValidationGroupsAwareInterface
            ? $manager->getValidationGroups('create', $document)
            : ['Default', 'Create'];

        if ($errors = $this->validate($document, $groups)) {
            return $errors;
        }

        return $manager->create($document);
    }

    /**
     * @param class-string                                           $entityClass
     * @param array<string, mixed>                                   $args
     * @param array<string, array{property: string, target: string}> $relations
     */
    private function handleUpdate(UpdatableManagerInterface $manager, string $entityClass, array $args, array $relations): object
    {
        /** @var string $nodeId */
        $nodeId = $args['id'];
        $id = $this->nodeIdResolver->decode($nodeId)->getId();
        unset($args['id']);

        $document = $manager->read($id);

        if (null === $document) {
            return new NodeNotFound($nodeId);
        }

        [$args, $violations] = $this->resolveRelations($args, $relations);

        if ($violations->count() > 0) {
            return ValidationErrorList::fromConstraintViolationList($violations);
        }

        $this->denormalizer->denormalize($args, $entityClass, context: [AbstractNormalizer::OBJECT_TO_POPULATE => $document]);

        $groups = $manager instanceof ValidationGroupsAwareInterface
            ? $manager->getValidationGroups('update', $document)
            : ['Default', 'Update'];

        if ($errors = $this->validate($document, $groups)) {
            return $errors;
        }

        return $manager->update($document);
    }

    /** @param array<string, mixed> $args */
    private function handleDelete(DeletableManagerInterface $manager, array $args): object
    {
        /** @var string $nodeId */
        $nodeId = $args['id'];
        $id = $this->nodeIdResolver->decode($nodeId)->getId();
        $document = $manager->read($id);

        if (null === $document) {
            return new NodeNotFound($nodeId);
        }

        return $manager->delete($document);
    }

    /**
     * Decode relation node IDs, resolve to target objects, and remap the arg key to the entity property name.
     *
     * @param array<string, mixed>                                   $args
     * @param array<string, array{property: string, target: string}> $relations
     *
     * @return array{0: array<string, mixed>, 1: ConstraintViolationList}
     */
    private function resolveRelations(array $args, array $relations): array
    {
        $violations = new ConstraintViolationList();

        foreach ($relations as $argName => $relation) {
            if (!\array_key_exists($argName, $args)) {
                continue;
            }

            $property = $relation['property'];
            $target = $relation['target'];

            $encodedId = $args[$argName];
            unset($args[$argName]);

            if (null === $encodedId) {
                $args[$property] = null;

                continue;
            }

            \assert(\is_string($encodedId));

            $id = $this->nodeIdResolver->decode($encodedId)->getId();
            $referenced = $this->managers->get($target)->read($id);

            if (null === $referenced) {
                $violations->add(new ConstraintViolation(
                    \sprintf('%s with ID "%s" not found.', $target, $encodedId),
                    null,
                    [],
                    null,
                    $property,
                    $encodedId,
                ));

                continue;
            }

            $args[$property] = $referenced;
        }

        return [$args, $violations];
    }

    /** @param list<string> $groups */
    private function validate(object $document, array $groups): ?ValidationErrorList
    {
        $violations = $this->validator->validate($document, null, $groups);

        if (0 === \count($violations)) {
            return null;
        }

        return ValidationErrorList::fromConstraintViolationList($violations);
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function flattenEnums(array $args): array
    {
        foreach ($args as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $args[$key] = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $args[$key] = $value->name;
            } elseif (\is_array($value)) {
                /** @var array<string, mixed> $value */
                $args[$key] = $this->flattenEnums($value);
            }
        }

        return $args;
    }
}
