<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException;

class Base64NodeIdCodec implements NodeIdCodecInterface
{
    public function encode(string $typeName, string $id): string
    {
        return base64_encode($typeName.':'.$id);
    }

    public function decode(string $encodedId): DecodedNodeId
    {
        $parts = explode(':', base64_decode($encodedId) ?: '', 2);

        if (2 !== \count($parts) || '' === $parts[0] || '' === $parts[1]) {
            throw new InvalidNodeIdArgumentException();
        }

        return new DecodedNodeId($parts[0], $parts[1]);
    }
}
