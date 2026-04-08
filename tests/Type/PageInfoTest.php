<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Type\PageInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\PageInfo
 */
class PageInfoTest extends TestCase
{
    private PageInfo $type;

    protected function setUp(): void
    {
        $this->type = new PageInfo();
    }

    public function testHasPreviousPageFieldIsNonNullBoolean(): void
    {
        $field = $this->type->getField('hasPreviousPage');

        $this->assertInstanceOf(NonNull::class, $field->getType());
        $this->assertSame(Type::boolean(), $field->getType()->getWrappedType());
    }

    public function testHasNextPageFieldIsNonNullBoolean(): void
    {
        $field = $this->type->getField('hasNextPage');

        $this->assertInstanceOf(NonNull::class, $field->getType());
        $this->assertSame(Type::boolean(), $field->getType()->getWrappedType());
    }

    public function testStartCursorFieldIsNullableString(): void
    {
        $field = $this->type->getField('startCursor');

        $this->assertSame(Type::string(), $field->getType());
    }

    public function testEndCursorFieldIsNullableString(): void
    {
        $field = $this->type->getField('endCursor');

        $this->assertSame(Type::string(), $field->getType());
    }

    public function testTotalCountFieldIsNullableInt(): void
    {
        $field = $this->type->getField('totalCount');

        $this->assertSame(Type::int(), $field->getType());
    }
}
