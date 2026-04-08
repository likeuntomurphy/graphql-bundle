<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\SchemaConfig;
use Likeuntomurphy\GraphQL\DefaultFieldResolver;
use Likeuntomurphy\GraphQL\SchemaConfigBuilder;
use Likeuntomurphy\GraphQL\Type\Mutation;
use Likeuntomurphy\GraphQL\Type\Query;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\SchemaConfigBuilder
 */
class SchemaConfigBuilderTest extends TestCase
{
    public function testBuildReturnsSchemaConfig(): void
    {
        $registry = $this->buildRegistry();
        $query = new Query(new \ArrayObject([]));
        $mutation = new Mutation(new \ArrayObject([]));
        $builder = new SchemaConfigBuilder($query, $registry, $mutation, new DefaultFieldResolver());

        $config = $builder->build();

        $this->assertInstanceOf(SchemaConfig::class, $config);
    }

    public function testTypeLoaderDelegatesToRegistry(): void
    {
        $registry = $this->buildRegistry();
        $query = new Query(new \ArrayObject([]));
        $mutation = new Mutation(new \ArrayObject([]));
        $builder = new SchemaConfigBuilder($query, $registry, $mutation, new DefaultFieldResolver());

        $config = $builder->build();
        $typeLoader = $config->typeLoader;

        $this->assertIsCallable($typeLoader);
        $this->assertSame(Type::string(), $typeLoader('String'));
    }

    public function testConfigIncludesMutationWhenMutationHasFields(): void
    {
        $registry = $this->buildRegistry();
        $query = new Query(new \ArrayObject([]));
        $mutation = new Mutation(new \ArrayObject([
            new FieldDefinition(['name' => 'createWidget', 'type' => Type::string()]),
        ]));
        $builder = new SchemaConfigBuilder($query, $registry, $mutation, new DefaultFieldResolver());

        $config = $builder->build();

        $this->assertSame($mutation, $config->mutation);
    }

    public function testConfigOmitsMutationWhenMutationHasNoFields(): void
    {
        $registry = $this->buildRegistry();
        $query = new Query(new \ArrayObject([]));
        $mutation = new Mutation(new \ArrayObject([]));
        $builder = new SchemaConfigBuilder($query, $registry, $mutation, new DefaultFieldResolver());

        $config = $builder->build();

        $this->assertNull($config->mutation);
    }

    private function buildRegistry(): TypeRegistry
    {
        return new TypeRegistry(new ServiceLocator([
            'String' => fn () => Type::string(),
        ]));
    }
}
