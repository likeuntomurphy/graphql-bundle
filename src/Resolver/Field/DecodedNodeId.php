<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

readonly class DecodedNodeId implements DecodedNodeIdInterface
{
    public function __construct(
        private string $typeName,
        private string $id,
    ) {
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
