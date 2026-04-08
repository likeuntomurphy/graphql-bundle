<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\DefaultFieldResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DefaultFieldResolver
 */
class DefaultFieldResolverTest extends TestCase
{
    private DefaultFieldResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultFieldResolver();
    }

    public function testReturnsPublicPropertyValue(): void
    {
        $source = new \stdClass();
        $source->title = 'Hello World';

        $result = ($this->resolver)($source, [], [], $this->resolveInfo('title'));

        $this->assertSame('Hello World', $result);
    }

    public function testReturnsNullWhenPropertyDoesNotExist(): void
    {
        $source = new \stdClass();
        $source->name = 'foo';

        $result = ($this->resolver)($source, [], [], $this->resolveInfo('missing'));

        $this->assertNull($result);
    }

    public function testReturnsNullWhenSourceIsNotAnObject(): void
    {
        $result = ($this->resolver)('scalar-value', [], [], $this->resolveInfo('title'));

        $this->assertNull($result);
    }

    public function testReturnsArrayValueByKey(): void
    {
        $source = ['title' => 'From Array'];

        $result = ($this->resolver)($source, [], [], $this->resolveInfo('title'));

        $this->assertSame('From Array', $result);
    }

    public function testReturnsValueViaGetter(): void
    {
        $source = new class {
            private string $name = 'widget';

            public function getName(): string
            {
                return $this->name;
            }
        };

        $result = ($this->resolver)($source, [], [], $this->resolveInfo('name'));

        $this->assertSame('widget', $result);
    }

    private function resolveInfo(string $fieldName): ResolveInfo
    {
        $type = new ObjectType([
            'name' => 'Query',
            'fields' => [
                $fieldName => Type::string(),
            ],
        ]);

        $info = (new \ReflectionClass(ResolveInfo::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(ResolveInfo::class, 'fieldName'))->setValue($info, $fieldName);
        (new \ReflectionProperty(ResolveInfo::class, 'parentType'))->setValue($info, $type);

        return $info;
    }
}
