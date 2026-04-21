<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use Likeuntomurphy\GraphQL\Type\NonEmptyString;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\NonEmptyString
 */
class NonEmptyStringTest extends TestCase
{
    private NonEmptyString $type;

    protected function setUp(): void
    {
        $this->type = new NonEmptyString();
    }

    public function testNameIsNonEmptyString(): void
    {
        $this->assertSame('NonEmptyString', $this->type->name);
    }

    public function testSerializePassesThroughString(): void
    {
        $this->assertSame('hello', $this->type->serialize('hello'));
    }

    public function testSerializeThrowsForNonString(): void
    {
        $this->expectException(InvariantViolation::class);

        $this->type->serialize(42);
    }

    public function testParseValueAcceptsNonEmptyString(): void
    {
        $this->assertSame('hello', $this->type->parseValue('hello'));
    }

    public function testParseValueThrowsForEmptyString(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue('');
    }

    public function testParseValueThrowsForWhitespaceOnly(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue("  \t\n");
    }

    public function testParseLiteralAcceptsStringNode(): void
    {
        $this->assertSame('hello', $this->type->parseLiteral(new StringValueNode(['value' => 'hello'])));
    }

    public function testParseLiteralThrowsForNonStringNode(): void
    {
        $this->expectException(Error::class);

        $this->type->parseLiteral(new IntValueNode(['value' => '42']));
    }
}
