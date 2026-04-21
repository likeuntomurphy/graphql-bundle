<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use Likeuntomurphy\GraphQL\Type\Url;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\Url
 */
class UrlTest extends TestCase
{
    private Url $type;

    protected function setUp(): void
    {
        $this->type = new Url();
    }

    public function testNameIsUrl(): void
    {
        $this->assertSame('Url', $this->type->name);
    }

    public function testSerializePassesThroughString(): void
    {
        $this->assertSame('https://example.com', $this->type->serialize('https://example.com'));
    }

    public function testSerializeThrowsForNonString(): void
    {
        $this->expectException(InvariantViolation::class);

        $this->type->serialize(42);
    }

    public function testParseValueAcceptsValidUrl(): void
    {
        $this->assertSame('https://example.com/path?q=1', $this->type->parseValue('https://example.com/path?q=1'));
    }

    public function testParseValueThrowsForInvalidFormat(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue('not a url');
    }

    public function testParseValueThrowsForNonHttpScheme(): void
    {
        $this->expectException(Error::class);

        $this->type->parseValue('javascript:alert(1)');
    }

    public function testParseValueAcceptsHttp(): void
    {
        $this->assertSame('http://example.com', $this->type->parseValue('http://example.com'));
    }

    public function testParseLiteralAcceptsStringNode(): void
    {
        $this->assertSame(
            'https://example.com',
            $this->type->parseLiteral(new StringValueNode(['value' => 'https://example.com'])),
        );
    }

    public function testParseLiteralThrowsForNonStringNode(): void
    {
        $this->expectException(Error::class);

        $this->type->parseLiteral(new IntValueNode(['value' => '42']));
    }
}
