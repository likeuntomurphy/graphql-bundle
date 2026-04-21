<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class Uuid extends ScalarType
{
    public const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public string $name = 'Uuid';

    public ?string $description = 'An RFC 9562 UUID.';

    public function serialize($value): string
    {
        if (!\is_string($value)) {
            throw new InvariantViolation('Uuid is not a string: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseValue(mixed $value): string
    {
        if (!\is_string($value) || 1 !== preg_match(self::PATTERN, $value)) {
            throw new Error('Expected a valid UUID, got: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Uuid must be provided as a string literal.', $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
