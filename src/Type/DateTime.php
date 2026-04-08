<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class DateTime extends ScalarType
{
    public string $name = 'DateTime';

    public ?string $description = 'The `DateTime` scalar type represents time data, represented as an ISO-8601 encoded UTC date string.';

    public function serialize($value): string
    {
        // Assume already serialized
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof \DateTimeImmutable) {
            throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: '.Utils::printSafe($value));
        }

        return $value->format(\DateTime::ATOM);
    }

    public function parseValue(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(\DateTime::ATOM, $value) ?: null;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?\DateTimeImmutable
    {
        if ($valueNode instanceof StringValueNode) {
            return $this->parseValue($valueNode->value);
        }

        return null;
    }
}
