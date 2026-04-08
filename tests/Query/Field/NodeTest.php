<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Query\Field;

use GraphQL\Type\Definition\NonNull;
use Likeuntomurphy\GraphQL\Query\Field\Node;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeResolver;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Query\Field\Node
 */
class NodeTest extends TestCase
{
    private NodeInterface $nodeInterface;
    private Node $field;

    protected function setUp(): void
    {
        $this->nodeInterface = new NodeInterface($this->createStub(ObjectTypeResolver::class));
        $resolver = $this->createStub(NodeResolver::class);
        $this->field = new Node($this->nodeInterface, $resolver);
    }

    public function testNameIsNode(): void
    {
        $this->assertSame('node', $this->field->name);
    }

    public function testTypeIsNodeInterface(): void
    {
        $this->assertSame($this->nodeInterface, $this->field->getType());
    }

    public function testHasNonNullIdArgument(): void
    {
        $arg = $this->field->getArg('id');

        $this->assertInstanceOf(NonNull::class, $arg?->getType());
    }

    public function testResolverIsNodeImplementorResolver(): void
    {
        $this->assertInstanceOf(NodeResolver::class, $this->field->resolveFn);
    }
}
