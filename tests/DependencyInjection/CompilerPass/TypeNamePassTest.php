<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\Attribute\Name;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeNamePass;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\AutoconfigureFailedException;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeNamePass
 */
class TypeNamePassTest extends AbstractCompilerPassTestCase
{
    public function testUsesClassShortnameWhenNoNameTagAttribute(): void
    {
        $definition = new Definition(ObjectType::class);
        $definition->addTag(TypeRegistry::TAG);
        $this->container->setDefinition(ObjectType::class, $definition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            ObjectType::class,
            TypeRegistry::TAG,
            ['name' => 'ObjectType'],
        );
    }

    public function testUsesNameAttributeWhenPresent(): void
    {
        // NamedType is an anonymous class stand-in; we use a real class with a Name attribute
        $className = TypeNamePassTest_NamedStub::class;

        $definition = new Definition($className);
        $definition->addTag(TypeRegistry::TAG);
        $this->container->setDefinition($className, $definition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $className,
            TypeRegistry::TAG,
            ['name' => 'CustomName'],
        );
    }

    public function testSkipsServicesAlreadyHavingNameTag(): void
    {
        $definition = new Definition(ObjectType::class);
        $definition->addTag(TypeRegistry::TAG, ['name' => 'AlreadyNamed']);
        $this->container->setDefinition(ObjectType::class, $definition);

        $this->compile();

        // Tag should remain unchanged
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            ObjectType::class,
            TypeRegistry::TAG,
            ['name' => 'AlreadyNamed'],
        );
    }

    public function testThrowsForNonClassServiceWithoutNameTag(): void
    {
        $this->expectException(AutoconfigureFailedException::class);

        $definition = new Definition();
        $definition->addTag(TypeRegistry::TAG);
        $this->container->setDefinition('not_a_class_service_id', $definition);

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TypeNamePass());
    }
}

#[Name('CustomName')]
class TypeNamePassTest_NamedStub
{
}
