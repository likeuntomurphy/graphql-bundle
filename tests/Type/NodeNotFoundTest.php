<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Type\NodeNotFound;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\NodeNotFound
 */
class NodeNotFoundTest extends TestCase
{
    private NodeNotFound $type;

    protected function setUp(): void
    {
        $this->type = new NodeNotFound();
    }

    public function testNameIsNodeNotFound(): void
    {
        $this->assertSame('NodeNotFound', $this->type->name);
    }

    public function testDescriptionIsSet(): void
    {
        $this->assertNotEmpty($this->type->description);
    }

    public function testIdFieldIsNonNullId(): void
    {
        $field = $this->type->getField('id');

        $this->assertInstanceOf(NonNull::class, $field->getType());
        $this->assertSame(Type::id(), $field->getType()->getWrappedType());
    }
}
