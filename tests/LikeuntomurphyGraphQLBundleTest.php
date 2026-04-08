<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests;

use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\EnumTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\MutationFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\StandardTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeNamePass;
use Likeuntomurphy\GraphQL\DependencyInjection\GraphQLExtension;
use Likeuntomurphy\GraphQL\LikeuntomurphyGraphQLBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\LikeuntomurphyGraphQLBundle
 */
class LikeuntomurphyGraphQLBundleTest extends TestCase
{
    public function testBuildRegistersStandardTypePass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, StandardTypePass::class);
    }

    public function testBuildRegistersTypeNamePass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, TypeNamePass::class);
    }

    public function testBuildRegistersGlobalObjectTypePass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, GlobalObjectTypePass::class);
    }

    public function testBuildRegistersEnumTypePass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, EnumTypePass::class);
    }

    public function testBuildRegistersQueryFieldPass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, QueryFieldPass::class);
    }

    public function testBuildRegistersMutationFieldPass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new LikeuntomurphyGraphQLBundle();

        $bundle->build($container);

        $this->assertContainsCompilerPassOfType($container, MutationFieldPass::class);
    }

    public function testGetContainerExtensionReturnsGraphQLExtension(): void
    {
        $bundle = new LikeuntomurphyGraphQLBundle();

        $this->assertInstanceOf(GraphQLExtension::class, $bundle->getContainerExtension());
    }

    private function assertContainsCompilerPassOfType(ContainerBuilder $container, string $class): void
    {
        $passes = $container->getCompilerPassConfig()->getPasses();

        foreach ($passes as $pass) {
            if ($pass instanceof $class) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('No compiler pass of type %s was registered.', $class));
    }
}
