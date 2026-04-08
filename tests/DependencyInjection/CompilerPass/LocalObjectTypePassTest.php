<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\LocalObjectTypePass;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Address;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Bad;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\CollidingParent;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\EventLocal;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\HasTree;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Measurement;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Outer;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\ParentDoc;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Profile;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Resolved;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Stats;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Toggle;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Venue;
use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Warehouse;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\LocalObjectTypePass
 */
class LocalObjectTypePassTest extends AbstractCompilerPassTestCase
{
    public function testRegistersLocalObjectTypeFromPlaceholder(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            TypeRegistry::TAG.'.Address',
            TypeRegistry::TAG,
            ['class' => Address::class, 'name' => 'Address'],
        );
    }

    public function testDefinitionClassIsObjectType(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $definition = $this->container->findDefinition(TypeRegistry::TAG.'.Address');

        $this->assertSame(ObjectType::class, $definition->getClass());
    }

    public function testDefinitionIsPublic(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $this->assertTrue($this->container->findDefinition(TypeRegistry::TAG.'.Address')->isPublic());
    }

    public function testConfigNameMatchesShortClassName(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Address')->getArgument(0);

        $this->assertSame('Address', $config['name']);
    }

    public function testResolvesStringFieldAsNonNull(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Address')->getArgument(0);

        $this->assertSame(
            TypeRegistry::STRING.'.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['street']['type'],
        );
    }

    public function testResolvesIntFieldAsNonNull(): void
    {
        $this->registerLocalType('Stats', Stats::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Stats')->getArgument(0);

        $this->assertSame(
            TypeRegistry::INT.'.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['count']['type'],
        );
    }

    public function testResolvesBoolFieldAsNonNull(): void
    {
        $this->registerLocalType('Toggle', Toggle::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Toggle')->getArgument(0);

        $this->assertSame(
            TypeRegistry::BOOLEAN.'.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['active']['type'],
        );
    }

    public function testResolvesFloatFieldAsNonNull(): void
    {
        $this->registerLocalType('Measurement', Measurement::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Measurement')->getArgument(0);

        $this->assertSame(
            TypeRegistry::FLOAT.'.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['value']['type'],
        );
    }

    public function testNullableFieldIsNotWrappedInNonNull(): void
    {
        $this->registerLocalType('Address', Address::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Address')->getArgument(0);

        $this->assertSame(
            TypeRegistry::STRING,
            (string) $config['fields']['city']['type'],
        );
    }

    public function testFieldDescriptionFromAttribute(): void
    {
        $this->registerLocalType('Venue', Venue::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Venue')->getArgument(0);

        $this->assertSame('The street address', $config['fields']['street']['description']);
    }

    public function testExcludedPropertyIsSkipped(): void
    {
        $this->registerLocalType('Warehouse', Warehouse::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Warehouse')->getArgument(0);

        $this->assertArrayHasKey('street', $config['fields']);
        $this->assertArrayNotHasKey('internal', $config['fields']);
    }

    public function testResolvesEnumFieldByShortName(): void
    {
        $this->registerLocalType('Profile', Profile::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Profile')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.Color.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['color']['type'],
        );
    }

    public function testResolvesDateTimeImmutableField(): void
    {
        $this->registerLocalType('EventLocal', EventLocal::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.EventLocal')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.DateTimeImmutable.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['startsAt']['type'],
        );
    }

    public function testFieldResolverFromAttribute(): void
    {
        $this->registerLocalType('Resolved', Resolved::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Resolved')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['fields']['computed']['resolve']);
        $this->assertSame('App\Resolver\CustomResolver', (string) $config['fields']['computed']['resolve']);
    }

    public function testNestedObjectFieldDescriptionFromAttribute(): void
    {
        $this->registerLocalType('Outer', Outer::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Outer')->getArgument(0);

        $this->assertSame('The nested inner object', $config['fields']['inner']['description']);
    }

    public function testNestedObjectFieldResolverFromAttribute(): void
    {
        $this->registerLocalType('ParentDoc', ParentDoc::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ParentDoc')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['fields']['child']['resolve']);
        $this->assertSame('App\Resolver\NestedResolver', (string) $config['fields']['child']['resolve']);
    }

    public function testSkipsTagsWithoutLocalAttribute(): void
    {
        $this->container->setDefinition(
            TypeRegistry::TAG.'.SomeGlobalType',
            (new Definition(ObjectType::class))
                ->setPublic(true)
                ->addTag(TypeRegistry::TAG, ['name' => 'SomeGlobalType']),
        );

        $this->compile();

        // The definition should remain untouched (no argument set by the pass)
        $definition = $this->container->findDefinition(TypeRegistry::TAG.'.SomeGlobalType');
        $this->assertEmpty($definition->getArguments());
    }

    public function testThrowsTypeNameCollisionForDuplicateLocalObjectNames(): void
    {
        $this->registerLocalType('Address', Address::class);
        $this->registerLocalType('CollidingParent', CollidingParent::class);

        $this->expectException(TypeNameCollisionException::class);
        $this->expectExceptionMessageMatches('/Address/');
        $this->expectExceptionMessageMatches('/#\[Likeuntomurphy\\\GraphQL\\\Attribute\\\Name\]/');

        $this->compile();
    }

    public function testThrowsForUnsupportedType(): void
    {
        $this->registerLocalType('Bad', Bad::class);

        $this->expectException(UnsupportedTypeException::class);

        $this->compile();
    }

    public function testResolvesCircularReferenceViaDeferredRegistration(): void
    {
        $this->registerLocalType('HasTree', HasTree::class);

        $this->compile();

        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.TreeNode');

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.TreeNode')->getArgument(0);

        $this->assertArrayHasKey('name', $config['fields']);
        $this->assertArrayHasKey('child', $config['fields']);
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new LocalObjectTypePass());
    }

    /**
     * @param class-string $className
     */
    private function registerLocalType(string $name, string $className): void
    {
        $this->container->setDefinition(
            TypeRegistry::TAG.'.'.$name,
            (new Definition(ObjectType::class))
                ->setPublic(true)
                ->addTag(TypeRegistry::TAG, ['class' => $className, 'name' => $name, 'local' => true]),
        );
    }
}
