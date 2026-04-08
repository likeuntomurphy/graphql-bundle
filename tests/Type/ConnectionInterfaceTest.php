<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use Likeuntomurphy\GraphQL\Field\Cursor;
use Likeuntomurphy\GraphQL\Field\PageInfo as PageInfoField;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Type\ConnectionInterface;
use Likeuntomurphy\GraphQL\Type\EdgeInterface;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use Likeuntomurphy\GraphQL\Type\PageInfo as PageInfoType;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\ConnectionInterface
 */
class ConnectionInterfaceTest extends TestCase
{
    private ConnectionInterface $type;

    protected function setUp(): void
    {
        $nodeInterface = new NodeInterface($this->createStub(ObjectTypeResolver::class));
        $edgeInterface = new EdgeInterface(
            new TypeRegistry(new ServiceLocator(['NodeInterface' => fn () => $nodeInterface])),
            new Cursor(),
        );
        $registry = new TypeRegistry(new ServiceLocator([
            'EdgeInterface' => fn () => $edgeInterface,
        ]));

        $this->type = new ConnectionInterface($registry, new PageInfoField(new PageInfoType()));
    }

    public function testHasEdgesField(): void
    {
        $field = $this->type->getField('edges');

        $this->assertInstanceOf(FieldDefinition::class, $field);
    }

    public function testEdgesFieldIsListType(): void
    {
        $field = $this->type->getField('edges');

        $this->assertInstanceOf(ListOfType::class, $field->getType());
    }

    public function testEdgesFieldHasDescription(): void
    {
        $field = $this->type->getField('edges');

        $this->assertSame(ConnectionInterface::EDGES_FIELD_DESCRIPTION, $field->description);
    }

    public function testHasPageInfoField(): void
    {
        $field = $this->type->getField('pageInfo');

        $this->assertInstanceOf(PageInfoField::class, $field);
    }

    public function testPageInfoFieldTypeIsPageInfo(): void
    {
        $field = $this->type->getField('pageInfo');

        $this->assertInstanceOf(PageInfoType::class, $field->getType());
    }
}
