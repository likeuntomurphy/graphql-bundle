<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class Url extends ScalarType
{
    public string $name = 'Url';

    public ?string $description = 'An http or https URL.';

    public function serialize($value): string
    {
        if (!\is_string($value)) {
            throw new InvariantViolation('Url is not a string: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseValue(mixed $value): string
    {
        if (!\is_string($value) || false === filter_var($value, \FILTER_VALIDATE_URL) || !\in_array(parse_url($value, \PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new Error('Expected a valid http or https URL, got: '.Utils::printSafe($value));
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Url must be provided as a string literal.', $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
