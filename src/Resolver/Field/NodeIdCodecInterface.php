<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

interface NodeIdCodecInterface
{
    public function encode(string $typeName, string $id): string;

    public function decode(string $encodedId): DecodedNodeIdInterface;
}
