<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\DateTime
 */
class DateTimeTest extends TestCase
{
    private \Likeuntomurphy\GraphQL\Type\DateTime $type;

    protected function setUp(): void
    {
        $this->type = new \Likeuntomurphy\GraphQL\Type\DateTime();
    }

    public function testNameIsDateTime(): void
    {
        $this->assertSame('DateTime', $this->type->name);
    }

    public function testSerializesDateTimeImmutableToAtomString(): void
    {
        $date = new \DateTimeImmutable('2024-06-15T12:30:00+00:00');

        $this->assertSame('2024-06-15T12:30:00+00:00', $this->type->serialize($date));
    }

    public function testSerializePassesThroughString(): void
    {
        $this->assertSame('2024-06-15T12:30:00+00:00', $this->type->serialize('2024-06-15T12:30:00+00:00'));
    }

    public function testSerializeThrowsForNonDateTimeValue(): void
    {
        $this->expectException(InvariantViolation::class);

        $this->type->serialize(42);
    }

    public function testParseValueReturnsDateTimeImmutable(): void
    {
        $result = $this->type->parseValue('2024-06-15T12:30:00+00:00');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2024-06-15T12:30:00+00:00', $result->format(\DateTime::ATOM));
    }

    public function testParseValueReturnsNullForInvalidString(): void
    {
        $this->assertNull($this->type->parseValue('not-a-date'));
    }

    public function testParseLiteralReturnsDateTimeImmutableForStringNode(): void
    {
        $node = new StringValueNode(['value' => '2024-06-15T12:30:00+00:00']);

        $result = $this->type->parseLiteral($node);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2024-06-15T12:30:00+00:00', $result->format(\DateTime::ATOM));
    }

    public function testParseLiteralReturnsNullForNonStringNode(): void
    {
        $node = new IntValueNode(['value' => '42']);

        $this->assertNull($this->type->parseLiteral($node));
    }
}
