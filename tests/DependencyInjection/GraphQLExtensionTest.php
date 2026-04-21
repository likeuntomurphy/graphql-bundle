<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection;

use Likeuntomurphy\GraphQL\DependencyInjection\GraphQLExtension;
use Likeuntomurphy\GraphQL\SchemaConfigBuilder;
use Likeuntomurphy\GraphQL\Type\Query;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\GraphQLExtension
 */
class GraphQLExtensionTest extends AbstractExtensionTestCase
{
    public function testTypeRegistryServiceIsRegistered(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(TypeRegistry::class);
    }

    public function testSchemaConfigBuilderServiceIsRegistered(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(SchemaConfigBuilder::class);
    }

    public function testQueryTypeServiceIsRegistered(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(Query::class);
    }

    protected function getContainerExtensions(): array
    {
        return [new GraphQLExtension()];
    }
}
