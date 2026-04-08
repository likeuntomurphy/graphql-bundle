<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Query\Field;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Field\Cursor;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Field\Cursor
 */
class CursorTest extends TestCase
{
    private Cursor $field;

    protected function setUp(): void
    {
        $this->field = new Cursor();
    }

    public function testNameIsCursor(): void
    {
        $this->assertSame('cursor', $this->field->name);
    }

    public function testTypeIsString(): void
    {
        $this->assertSame(Type::string(), $this->field->getType());
    }

    public function testHasDescription(): void
    {
        $this->assertNotEmpty($this->field->description);
    }
}
