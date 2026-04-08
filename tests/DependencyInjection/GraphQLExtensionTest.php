<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection;

use Likeuntomurphy\GraphQL\DependencyInjection\GraphQLExtension;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
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

    public function testPaginationLimitDefaultsToRequestConstant(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter(
            'likeuntomurphy_graphql.pagination.limit',
            CursorPaginationParams::LIMIT,
        );
    }

    public function testPaginationLimitIsConfigurable(): void
    {
        $this->load(['pagination' => ['limit' => 25]]);

        $this->assertContainerBuilderHasParameter(
            'likeuntomurphy_graphql.pagination.limit',
            25,
        );
    }

    protected function getContainerExtensions(): array
    {
        return [new GraphQLExtension()];
    }
}
