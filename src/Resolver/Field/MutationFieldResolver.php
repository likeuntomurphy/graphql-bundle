<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\ReadableManagerInterface;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Service\ServiceProviderInterface;

class MutationFieldResolver
{
    /** @param ServiceProviderInterface<GlobalObjectManagerInterface> $managers */
    public function __construct(
        private NodeIdResolver $nodeIdResolver,
        private DenormalizerInterface $denormalizer,
        #[AutowireLocator(GlobalObjectManagerInterface::TAG, indexAttribute: 'key')] private ServiceProviderInterface $managers,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @param list<string>         $idFields
     */
    public function resolve(string $method, string $typeName, array $args, array $idFields = []): object
    {
        $manager = $this->managers->get($typeName);

        /** @var list<\UnitEnum> $validationGroups */
        $validationGroups = $args['validationGroups'] ?? [];
        unset($args['validationGroups']);
        $args = $this->decodeIdFields($args, $idFields);
        $args = $this->flattenEnums($args);

        try {
            return match ($method) {
                'create' => $this->handleCreate($manager, $args, $validationGroups),
                'update' => $this->handleUpdate($manager, $args, $validationGroups),
                'delete' => $this->handleDelete($manager, $args),
                default => throw new UnknownMutationMethodException($method),
            };
        } catch (ValidationFailedException $exception) {
            return ValidationErrorList::fromConstraintViolationList($exception->getViolations());
        }
    }

    /**
     * @param array<string, mixed> $args
     * @param list<\UnitEnum>      $validationGroups
     */
    private function handleCreate(GlobalObjectManagerInterface $manager, array $args, array $validationGroups): object
    {
        \assert($manager instanceof CreatableManagerInterface);

        /** @var object $dto */
        $dto = $this->denormalizer->denormalize($args, $manager::getManagedDataTransferObject());

        return $manager->create($dto, new ($manager::getManagedGlobalObject())(), $validationGroups);
    }

    /** @param array<string, mixed> $args */
    private function handleDelete(GlobalObjectManagerInterface $manager, array $args): object
    {
        \assert($manager instanceof ReadableManagerInterface);
        \assert($manager instanceof DeletableManagerInterface);

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
     * @param array<string, mixed> $args
     * @param list<\UnitEnum>      $validationGroups
     */
    private function handleUpdate(GlobalObjectManagerInterface $manager, array $args, array $validationGroups): object
    {
        \assert($manager instanceof ReadableManagerInterface);
        \assert($manager instanceof UpdatableManagerInterface);

        /** @var string $nodeId */
        $nodeId = $args['id'];
        $id = $this->nodeIdResolver->decode($nodeId)->getId();
        unset($args['id']);

        $document = $manager->read($id);

        if (null === $document) {
            return new NodeNotFound($nodeId);
        }

        /** @var object $dto */
        $dto = $this->denormalizer->denormalize($args, $manager::getManagedDataTransferObject());

        return $manager->update($dto, $document, $validationGroups);
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

    /**
     * @param array<string, mixed> $args
     * @param list<string>         $idFields
     *
     * @return array<string, mixed>
     */
    private function decodeIdFields(array $args, array $idFields): array
    {
        foreach ($idFields as $name) {
            if (isset($args[$name]) && \is_string($args[$name])) {
                $args[$name] = $this->nodeIdResolver->decode($args[$name])->getId();
            }
        }

        return $args;
    }
}
