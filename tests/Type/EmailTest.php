<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use Likeuntomurphy\GraphQL\Type\Email;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\Email
 */
class EmailTest extends TestCase
{
    private Email $type;

    protected function setUp(): void
    {
        $this->type = new Email();
    }

    public function testNameIsEmail(): void
    {
        $this->assertSame('Email', $this->type->name);
    }

    public function testSerializePassesThroughString(): void
    {
        $this->assertSame('a@b.com', $this->type->serialize('a@b.com'));
    }

    public function testSerializeThrowsForNonString(): void
    {
        $this->expectException(InvariantViolation::class);

        $this->type->serialize(42);
    }

    public function testParseValueAcceptsValidEmail(): void
    {
        $this->assertSame('a@b.com', $this->type->parseValue('a@b.com'));
    }

    public function testParseValueThrowsForInvalidFormat(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue('not-an-email');
    }

    public function testParseValueThrowsForNonString(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue(42);
    }

    public function testParseLiteralAcceptsStringNode(): void
    {
        $this->assertSame('a@b.com', $this->type->parseLiteral(new StringValueNode(['value' => 'a@b.com'])));
    }

    public function testParseLiteralThrowsForNonStringNode(): void
    {
        $this->expectException(Error::class);

        $this->type->parseLiteral(new IntValueNode(['value' => '42']));
    }
}
