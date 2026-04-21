<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass;
use Likeuntomurphy\GraphQL\Exception\InvalidGlobalObjectException;
use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use Likeuntomurphy\GraphQL\Field\NodeId;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Attachment;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Event;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Task;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\WithResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\WithTypeOverride;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\CollidingProjectManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ConnectionFieldManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\EventManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\IdFieldManager;
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

        $this->registerEntity(Project::class, ProjectManager::class);
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
        $this->assertCount(10, $this->getConfig()['fields']);
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
        $this->registerEntity(WithResolver::class, WithResolverManager::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.WithResolver')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['fields']['secret']['resolve']);
        $this->assertSame('App\Resolver\SecretResolver', (string) $config['fields']['secret']['resolve']);
    }

    public function testTypeAttributeOverridesPrimitiveMapping(): void
    {
        $this->registerEntity(WithTypeOverride::class, IdFieldManager::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.WithTypeOverride')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.Email.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['email']['type'],
        );

        $this->assertSame(
            TypeRegistry::STRING.'.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['plainString']['type'],
        );
    }

    public function testObjectPropertyResolvesToTypeReference(): void
    {
        $this->registerEntity(Task::class, TaskManager::class);

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.Task')->getArgument(0);

        $this->assertSame(
            TypeRegistry::TAG.'.Status.'.TypeRegistry::NON_NULL_SUFFIX,
            (string) $config['fields']['status']['type'],
        );
    }

    public function testResolvesDateTimeImmutableFieldToDateTimeScalar(): void
    {
        $this->registerEntity(Event::class, EventManager::class);

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
        $this->container->setDefinition(InvalidManager::class, new Definition(InvalidManager::class));
        $this->container->setDefinition(
            'NonExistent',
            (new Definition('NonExistent'))->addResourceTag(GlobalObject::RESOURCE_TAG, ['manager' => InvalidManager::class]),
        );

        $this->expectException(InvalidGlobalObjectException::class);
        $this->expectExceptionMessageMatches('/NonExistent/');

        $this->compile();
    }

    public function testThrowsTypeNameCollisionForDuplicateGlobalObjectNames(): void
    {
        $this->registerEntity(\Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Collision\Project::class, CollidingProjectManager::class);

        $this->expectException(TypeNameCollisionException::class);
        $this->expectExceptionMessageMatches('/Project/');
        $this->expectExceptionMessageMatches('/#\[Likeuntomurphy\\\GraphQL\\\Attribute\\\Name\]/');

        $this->compile();
    }

    public function testSkipsConnectionFieldsFromTypeDefinition(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, ConnectionFieldManager::class);
        $this->registerEntity(Attachment::class, IdFieldManager::class);

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

    /**
     * @param class-string $entityClass
     * @param class-string $managerClass
     */
    private function registerEntity(string $entityClass, string $managerClass): void
    {
        $this->container->setDefinition($managerClass, new Definition($managerClass));
        $this->container->setDefinition(
            $entityClass,
            (new Definition($entityClass))->addResourceTag(GlobalObject::RESOURCE_TAG, ['manager' => $managerClass]),
        );
    }
}
