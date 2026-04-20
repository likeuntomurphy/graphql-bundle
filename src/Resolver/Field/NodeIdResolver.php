<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;

class NodeIdResolver
{
    public function __construct(
        private TypeRegistry $typeRegistry,
        private NodeIdCodecInterface $codec,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(
        mixed $source,
        array $args,
        mixed $context,
        ResolveInfo $info,
    ): string {
        if (!$source instanceof GlobalObjectInterface) {
            throw new \LogicException(\sprintf('Expected source to be %s, got %s.', GlobalObjectInterface::class, get_debug_type($source)));
        }

        return $this->codec->encode($info->parentType->name, $source->getId());
    }

    public function decode(string $encodedId): DecodedNodeIdInterface
    {
        $nodeId = $this->codec->decode($encodedId);

        if (!$this->typeRegistry->has($nodeId->getTypeName())) {
            throw new InvalidNodeIdArgumentException();
        }

        return $nodeId;
    }
}
