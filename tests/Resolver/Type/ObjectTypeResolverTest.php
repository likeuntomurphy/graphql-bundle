<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\ObjectTypeResolutionException;
use Likeuntomurphy\GraphQL\Exception\UnknownTypeException;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver
 */
class ObjectTypeResolverTest extends TestCase
{
    public function testResolvesTypeFromClassMap(): void
    {
        $projectType = new ObjectType(['name' => 'Project', 'fields' => ['id' => Type::id()]]);
        $registry = new TypeRegistry(new ServiceLocator([
            'Project' => fn () => $projectType,
        ]));

        $resolver = new ObjectTypeResolver($registry, [Project::class => 'Project']);

        $result = $resolver(new Project(), [], $this->createStub(ResolveInfo::class));

        $this->assertSame($projectType, $result);
    }

    public function testResolvesInternalModelType(): void
    {
        $errorListType = new ObjectType(['name' => 'ValidationErrorList', 'fields' => ['errors' => Type::string()]]);
        $registry = new TypeRegistry(new ServiceLocator([
            'ValidationErrorList' => fn () => $errorListType,
        ]));

        $resolver = new ObjectTypeResolver($registry, [ValidationErrorList::class => 'ValidationErrorList']);

        $result = $resolver(new ValidationErrorList([]), [], $this->createStub(ResolveInfo::class));

        $this->assertSame($errorListType, $result);
    }

    public function testThrowsForUnmappedClass(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([]));
        $resolver = new ObjectTypeResolver($registry, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No type mapping/');

        $resolver(new Project(), [], $this->createStub(ResolveInfo::class));
    }

    public function testThrowsUnknownTypeExceptionForUnregisteredType(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([]));
        $resolver = new ObjectTypeResolver($registry, [Project::class => 'Project']);

        $this->expectException(UnknownTypeException::class);

        $resolver(new Project(), [], $this->createStub(ResolveInfo::class));
    }

    public function testThrowsObjectTypeResolutionExceptionWhenTypeIsNotObjectType(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([
            'Project' => fn () => Type::string(),
        ]));
        $resolver = new ObjectTypeResolver($registry, [Project::class => 'Project']);

        $this->expectException(ObjectTypeResolutionException::class);

        $resolver(new Project(), [], $this->createStub(ResolveInfo::class));
    }
}
