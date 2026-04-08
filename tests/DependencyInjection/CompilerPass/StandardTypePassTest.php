<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\StandardTypePass;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\StandardTypePass
 */
class StandardTypePassTest extends AbstractCompilerPassTestCase
{
    public function testRegistersAllStandardGraphQLTypes(): void
    {
        $this->compile();

        foreach (Type::getStandardTypes() as $name => $instance) {
            $serviceId = TypeRegistry::TAG.".{$name}";
            $this->assertContainerBuilderHasService($serviceId);
        }
    }

    public function testEachStandardTypeHasCorrectNameTag(): void
    {
        $this->compile();

        foreach (Type::getStandardTypes() as $name => $instance) {
            $serviceId = TypeRegistry::TAG.".{$name}";
            $this->assertContainerBuilderHasServiceDefinitionWithTag(
                $serviceId,
                TypeRegistry::TAG,
                ['name' => $name],
            );
        }
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new StandardTypePass());
    }
}
