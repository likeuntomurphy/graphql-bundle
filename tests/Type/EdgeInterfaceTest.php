<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\FieldDefinition;
use Likeuntomurphy\GraphQL\Field\Cursor;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Type\EdgeInterface;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\EdgeInterface
 */
class EdgeInterfaceTest extends TestCase
{
    private EdgeInterface $type;

    protected function setUp(): void
    {
        $nodeInterface = new NodeInterface($this->createStub(ObjectTypeResolver::class));
        $registry = new TypeRegistry(new ServiceLocator([
            'NodeInterface' => fn () => $nodeInterface,
        ]));

        $this->type = new EdgeInterface($registry, new Cursor());
    }

    public function testHasNodeField(): void
    {
        $field = $this->type->getField('node');

        $this->assertInstanceOf(FieldDefinition::class, $field);
    }

    public function testNodeFieldHasDescription(): void
    {
        $field = $this->type->getField('node');

        $this->assertSame(EdgeInterface::NODE_FIELD_DESCRIPTION, $field->description);
    }

    public function testHasCursorField(): void
    {
        $field = $this->type->getField('cursor');

        $this->assertInstanceOf(Cursor::class, $field);
    }
}
