<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Query\Field;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Field\NodeId;
use Likeuntomurphy\GraphQL\Resolver\Field\Base64NodeIdCodec;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Field\NodeId
 */
class NodeIdTest extends TestCase
{
    private NodeId $field;

    protected function setUp(): void
    {
        $this->field = new NodeId(new NodeIdResolver(new TypeRegistry(new ServiceLocator([])), new Base64NodeIdCodec()));
    }

    public function testNameIsId(): void
    {
        $this->assertSame('id', $this->field->name);
    }

    public function testTypeIsNonNullId(): void
    {
        $this->assertInstanceOf(NonNull::class, $this->field->getType());
        $this->assertSame(Type::id(), $this->field->getType()->getWrappedType());
    }

    public function testResolverIsNodeIdResolver(): void
    {
        $this->assertInstanceOf(\Closure::class, $this->field->resolveFn);
        $this->assertInstanceOf(NodeIdResolver::class, (new \ReflectionFunction($this->field->resolveFn))->getClosureThis());
    }
}
