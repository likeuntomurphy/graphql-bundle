<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\TypeRegistry
 */
class TypeRegistryTest extends TestCase
{
    public function testGetReturnsRegisteredType(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([
            'String' => fn () => Type::string(),
        ]));

        $this->assertSame(Type::string(), $registry->get('String'));
    }

    public function testGetReturnsNullForUnregisteredId(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([]));

        $this->assertNull($registry->get('Widget'));
    }

    public function testHasReturnsTrueForRegisteredType(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([
            'String' => fn () => Type::string(),
        ]));

        $this->assertTrue($registry->has('String'));
    }

    public function testHasReturnsFalseForUnregisteredType(): void
    {
        $registry = new TypeRegistry(new ServiceLocator([]));

        $this->assertFalse($registry->has('Widget'));
    }

    public function testGetReturnsEnumType(): void
    {
        $enumType = new EnumType(['name' => 'Color', 'values' => ['RED', 'GREEN', 'BLUE']]);
        $registry = new TypeRegistry(new ServiceLocator([
            'Color' => fn () => $enumType,
        ]));

        $this->assertSame($enumType, $registry->get('Color'));
    }
}
