<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\NodeInterface
 */
class NodeInterfaceTest extends TestCase
{
    public function testIdFieldIsNonNullId(): void
    {
        $nodeInterface = new NodeInterface($this->createStub(ObjectTypeResolver::class));

        $field = $nodeInterface->getField('id');

        $this->assertInstanceOf(NonNull::class, $field->getType());
        $this->assertSame(Type::id(), $field->getType()->getWrappedType());
    }

    public function testIdFieldHasDescription(): void
    {
        $nodeInterface = new NodeInterface($this->createStub(ObjectTypeResolver::class));

        $field = $nodeInterface->getField('id');

        $this->assertNotEmpty($field->description);
    }

    public function testResolveTypeReturnsTypeFromClassMap(): void
    {
        $projectType = new ObjectType(['name' => 'Project', 'fields' => ['id' => Type::id()]]);
        $registry = new TypeRegistry(new ServiceLocator([
            'Project' => fn () => $projectType,
        ]));

        $nodeInterface = new NodeInterface(new ObjectTypeResolver($registry, [Project::class => 'Project']));

        $resolved = $nodeInterface->resolveType(new Project(), [], $this->createStub(ResolveInfo::class));

        $this->assertSame($projectType, $resolved);
    }

    public function testResolveTypeThrowsForUnmappedClass(): void
    {
        $nodeInterface = new NodeInterface(new ObjectTypeResolver(new TypeRegistry(new ServiceLocator([])), []));

        $this->expectException(\RuntimeException::class);
        $nodeInterface->resolveType(new Project(), [], $this->createStub(ResolveInfo::class));
    }
}
