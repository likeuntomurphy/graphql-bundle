<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class NonEmptyString extends ScalarType
{
    public string $name = 'NonEmptyString';

    public ?string $description = 'A string containing at least one non-whitespace character.';

    public function serialize($value): string
    {
        if (!\is_string($value)) {
            throw new InvariantViolation('NonEmptyString is not a string: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseValue(mixed $value): string
    {
        if (!\is_string($value) || '' === trim($value)) {
            throw new Error('Expected a non-empty string, got: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('NonEmptyString must be provided as a string literal.', $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
