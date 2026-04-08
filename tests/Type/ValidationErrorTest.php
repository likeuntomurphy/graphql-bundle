<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Type\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\ValidationError
 */
class ValidationErrorTest extends TestCase
{
    private ValidationError $type;

    protected function setUp(): void
    {
        $this->type = new ValidationError();
    }

    public function testNameIsValidationError(): void
    {
        $this->assertSame('ValidationError', $this->type->name);
    }

    public function testDescriptionIsSet(): void
    {
        $this->assertSame('Represents a validation error for mutation input', $this->type->description);
    }

    public function testPathFieldIsString(): void
    {
        $this->assertSame(Type::string(), $this->type->getField('path')->getType());
    }

    public function testMessageFieldIsString(): void
    {
        $this->assertSame(Type::string(), $this->type->getField('message')->getType());
    }
}
