<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\EnumTypePass;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\Color;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\EnumTypePass
 */
class EnumTypePassTest extends AbstractCompilerPassTestCase
{
    public function testReplacesPlaceholderWithPhpEnumType(): void
    {
        $this->registerEnumPlaceholder('color', Color::class, 'Color');

        $this->compile();

        $definition = $this->container->findDefinition('color');

        $this->assertSame(PhpEnumType::class, $definition->getClass());
    }

    public function testReplacedDefinitionIsPublic(): void
    {
        $this->registerEnumPlaceholder('color', Color::class, 'Color');

        $this->compile();

        $definition = $this->container->findDefinition('color');

        $this->assertTrue($definition->isPublic());
    }

    public function testReplacedDefinitionIsTaggedWithName(): void
    {
        $this->registerEnumPlaceholder('color', Color::class, 'Color');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'color',
            TypeRegistry::TAG,
            ['name' => 'Color'],
        );
    }

    public function testReplacedDefinitionHasEnumClassAsArgument(): void
    {
        $this->registerEnumPlaceholder('color', Color::class, 'Color');

        $this->compile();

        $definition = $this->container->findDefinition('color');

        $this->assertSame(Color::class, $definition->getArgument(0));
    }

    public function testSkipsTagsWithoutEnumAttribute(): void
    {
        $definition = new Definition(ObjectType::class);
        $definition->addTag(TypeRegistry::TAG, ['class' => Color::class, 'name' => 'Color']);
        $this->setDefinition('color', $definition);

        $this->compile();

        $definition = $this->container->findDefinition('color');

        $this->assertSame(ObjectType::class, $definition->getClass());
    }

    public function testSkipsTagsWithoutClassAttribute(): void
    {
        $definition = new Definition(ObjectType::class);
        $definition->addTag(TypeRegistry::TAG, ['enum' => true, 'name' => 'Color']);
        $this->setDefinition('color', $definition);

        $this->compile();

        $definition = $this->container->findDefinition('color');

        $this->assertSame(ObjectType::class, $definition->getClass());
    }

    public function testDoesNothingWhenNoTaggedServices(): void
    {
        $this->compile();

        $this->addToAssertionCount(1);
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new EnumTypePass());
    }

    private function registerEnumPlaceholder(string $serviceId, string $enumClass, string $name): void
    {
        $definition = new Definition(ObjectType::class);
        $definition->addTag(TypeRegistry::TAG, [
            'class' => $enumClass,
            'name' => $name,
            'enum' => true,
        ]);
        $this->setDefinition($serviceId, $definition);
    }
}
