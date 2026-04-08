<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass;
use Likeuntomurphy\GraphQL\Exception\InvalidGlobalObjectException;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Field\NodeId;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\CollidingProjectManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ConnectionFieldManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\EventManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\InvalidManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ProjectManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\TaskManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\WithResolverManager;
use Likeuntomurphy\GraphQL\Type\NodeInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Attribute\Description
 * @covers \Likeuntomurphy\GraphQL\Attribute\Exclude
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass
 */
class GlobalObjectTypePassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->setDefinition(
            ProjectManager::class,
            (new Definition(ProjectManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );
    }

    public function testTaggedWithShortName(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            TypeRegistry::TAG.'.Project',
            TypeRegistry::TAG,
            ['name' => 'Project'],
        );
    }

    public function testConfigName(): void
    {
        $this->assertEquals('Project', $this->getConfig()['name']);
    }

    public function testConfigInterfaces(): void
    {
        $config = $this->getConfig();

        $this->assertCount(1, $config['interfaces']);
        $this->assertEquals(NodeInterface::class, (string) $config['interfaces'][0]);
    }

    public function testConfigFieldCount(): void
    {
        $this->assertCount(9, $this->getConfig()['fields']);
    }

    public function testIdFieldIsNodeIdReference(): void
    {
        $config = $this->getConfig();

        $this->assertInstanceOf(Reference::class, $config['fields']['id']);
        $this->assertEquals(NodeId::class, (string) $config['fields']['id']);
    }

    public function testConfigFieldsDoesNotIncludeExcludedField(): void
    {
        $config = $this->getConfig();

        $this->assertArrayHasKey('boolean', $config['fields']);
        $this->assertArrayNotHasKey('exclude', $config['fields']);
    }

    #[DataProvider('provideConfigFields')]
    public function testConfigFields(string $field, string $type, string $description): void
    {
        $config = $this->getConfig();
        $fieldDef = $config['fields'][$field];

        $this->assertEquals($type, (string) $fieldDef['type']);
        $this->assertEquals($description, $fieldDef['description']);
    }

    /** @return array<string, array<string, string>> */
    public static function provideConfigFields(): array
    {
        return [
            'int' => [
                'field' => 'int',
                'type' => TypeRegistry::INT.'.'.TypeRegistry::NON_NULL_SUFFIX,
                'description' => 'This is an int field',
            ],
            'float' => [
                'field' => 'float',
                'type' => TypeRegistry::FLOAT.'.'.TypeRegistry::NON_NULL_SUFFIX,
                'description' => 'This is a float field',
            ],
            'string' => [
                'field' => 'string',
                'type' => TypeRegistry::STRING.'.'.TypeRegistry::NON_NULL_SUFFIX,
                'description' => 'This is a string field',
            ],
            'boolean' => [
                'field' => 'boolean',
                'type' => TypeRegistry::BOOLEAN.'.'.TypeRegistry::NON_NULL_SUFFIX,
                'description' => 'This is a boolean field',
            ],
            'nullableInt' => [
                'field' => 'nullableInt',
                'type' => TypeRegistry::INT,
                'description' => 'This is a nullable int field',
            ],
            'nullableFloat' => [
                'field' => 'nullableFloat',
                'type' => TypeRegistry::FLOAT,
                'description' => 'This is a nullable float field',
            ],
            'nullableString' => [
                'field' => 'nullableString',
                'type' => TypeRegistry::STRING,
                'description' => 'This is a nullable string field',
            ],
            'nullableBoolean' => [
                'field' => 'nullableBoolean',
                'type' => TypeRegistry::BOOLEAN,
                'description' => 'This is a nullable boolean field',
            ],
        ];
    }

    public function testFieldResolverFromAttribute(): void
    {
        $this->container->setDefinition(
            WithResolverManager::class,
            (new Definition(WithResolverManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.WithResolver')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['fields']['secret']['resolve']);
        $this->assertSame('App\Resolver\SecretResolver', (string) $config['fields']['secret']['resolve']);
    }

    public function testObjectPropertyResolvesToTypeReference(): void
    {
        $this->container->setDefinition(
            TaskManager::class,
            (new Definition(TaskManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Task')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.Status.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['status']['type'],
        );
    }

    public function testResolvesDateTimeImmutableFieldToDateTimeScalar(): void
    {
        $this->container->setDefinition(
            EventManager::class,
            (new Definition(EventManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Event')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.DateTimeImmutable.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['startsAt']['type'],
        );

        $this->assertSame(
            TypeRegistry::TAG.'.DateTimeImmutable',
            (string) $config['fields']['endsAt']['type'],
        );
    }

    public function testThrowsInvalidGlobalObjectExceptionForNonExistentClass(): void
    {
        $this->container->setDefinition(
            InvalidManager::class,
            (new Definition(InvalidManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->expectException(InvalidGlobalObjectException::class);
        $this->expectExceptionMessageMatches('/InvalidManager/');

        $this->compile();
    }

    public function testThrowsTypeNameCollisionForDuplicateGlobalObjectNames(): void
    {
        $this->container->setDefinition(
            CollidingProjectManager::class,
            (new Definition(CollidingProjectManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->expectException(TypeNameCollisionException::class);
        $this->expectExceptionMessageMatches('/Project/');
        $this->expectExceptionMessageMatches('/#\[Likeuntomurphy\\\GraphQL\\\Attribute\\\Name\]/');

        $this->compile();
    }

    public function testSkipsConnectionFieldsFromTypeDefinition(): void
    {
        $this->container->setDefinition(
            ConnectionFieldManager::class,
            (new Definition(ConnectionFieldManager::class))
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectWithAttachments')->getArgument(0);

        $this->assertArrayHasKey('name', $config['fields']);
        $this->assertArrayNotHasKey('attachments', $config['fields']);
    }

    public function testDoesNothingWhenNoManagersRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new GlobalObjectTypePass());
        $container->compile();

        $this->assertFalse($container->has(TypeRegistry::TAG.'.Project'));
    }

    public function testAddsKeyTagToManagerDefinition(): void
    {
        $this->compile();

        $definition = $this->container->findDefinition(ProjectManager::class);
        $tags = $definition->getTag(GlobalObjectManagerInterface::TAG);

        $found = false;
        foreach ($tags as $attributes) {
            if (isset($attributes['key']) && 'Project' === $attributes['key']) {
                $found = true;

                break;
            }
        }

        $this->assertTrue($found, 'Expected manager definition to have a tag with key "Project"');
    }

    /** @return array{name: string, interfaces: list<Reference>, fields: array<string, mixed>} */
    protected function getConfig(): array
    {
        $this->compile();

        return $this->container->findDefinition(TypeRegistry::TAG.'.Project')->getArgument(0);
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GlobalObjectTypePass());
    }
}
