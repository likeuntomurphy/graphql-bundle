<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Resolver\Field\Base64NodeIdCodec;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver
 */
class NodeIdResolverTest extends TestCase
{
    private ObjectType $parentType;
    private NodeIdResolver $resolver;

    protected function setUp(): void
    {
        $this->parentType = new ObjectType(['name' => 'Widget', 'fields' => ['id' => Type::id()]]);
        $this->resolver = new NodeIdResolver(new TypeRegistry(new ServiceLocator([])), new Base64NodeIdCodec());
    }

    public function testReturnsBase64EncodedGlobalId(): void
    {
        $source = $this->createStub(GlobalObjectInterface::class);
        $source->method('getId')->willReturn('42');

        $result = ($this->resolver)($source, [], [], $this->resolveInfo());

        $this->assertSame(base64_encode('Widget:42'), $result);
    }

    public function testDecodeReturnsDecodedNodeId(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([
            'Widget' => fn () => $this->parentType,
        ]));
        $resolver = new NodeIdResolver($registry, new Base64NodeIdCodec());

        $nodeId = $resolver->decode(base64_encode('Widget:42'));

        $this->assertSame('Widget', $nodeId->getTypeName());
        $this->assertSame('42', $nodeId->getId());
    }

    public function testDecodeThrowsForUnknownType(): void
    {
        $this->expectException(InvalidNodeIdArgumentException::class);

        $this->resolver->decode(base64_encode('Unknown:42'));
    }

    private function resolveInfo(): ResolveInfo
    {
        $info = (new \ReflectionClass(ResolveInfo::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(ResolveInfo::class, 'fieldName'))->setValue($info, 'id');
        (new \ReflectionProperty(ResolveInfo::class, 'parentType'))->setValue($info, $this->parentType);

        return $info;
    }
}
