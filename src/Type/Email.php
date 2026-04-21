<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class Email extends ScalarType
{
    public string $name = 'Email';

    public ?string $description = 'An RFC 5322 email address.';

    public function serialize($value): string
    {
        if (!\is_string($value)) {
            throw new InvariantViolation('Email is not a string: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseValue(mixed $value): string
    {
        if (!\is_string($value) || false === filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw new Error('Expected a valid email address, got: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Email must be provided as a string literal.', $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
