<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use Likeuntomurphy\GraphQL\Type\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\Uuid
 */
class UuidTest extends TestCase
{
    private const string VALID_V4 = '6fa459ea-ee8a-4b0c-b4fe-6a4e6b0f12ab';

    private Uuid $type;

    protected function setUp(): void
    {
        $this->type = new Uuid();
    }

    public function testNameIsUuid(): void
    {
        $this->assertSame('Uuid', $this->type->name);
    }

    public function testSerializePassesThroughString(): void
    {
        $this->assertSame(self::VALID_V4, $this->type->serialize(self::VALID_V4));
    }

    public function testSerializeThrowsForNonString(): void
    {
        $this->expectException(InvariantViolation::class);

        $this->type->serialize(42);
    }

    public function testParseValueAcceptsValidUuid(): void
    {
        $this->assertSame(self::VALID_V4, $this->type->parseValue(self::VALID_V4));
    }

    public function testParseValueThrowsForInvalidFormat(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue('not-a-uuid');
    }

    public function testParseLiteralAcceptsStringNode(): void
    {
        $this->assertSame(
            self::VALID_V4,
            $this->type->parseLiteral(new StringValueNode(['value' => self::VALID_V4])),
        );
    }

    public function testParseLiteralThrowsForNonStringNode(): void
    {
        $this->expectException(Error::class);

        $this->type->parseLiteral(new IntValueNode(['value' => '42']));
    }
}
