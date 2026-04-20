<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\ConnectionFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass;
use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ConnectionFieldManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\InvalidConnectionManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ListableManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\NonGenericConnectionManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ScalarGenericConnectionManager;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\ConnectionFieldPass
 */
class ConnectionFieldPassTest extends AbstractCompilerPassTestCase
{
    public function testAddsConnectionFieldToParentObjectType(): void
    {
        $this->registerManagers();

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectWithAttachments')->getArgument(0);

        $this->assertArrayHasKey('attachments', $config['fields']);
    }

    public function testConnectionFieldTypeIsConnectionReference(): void
    {
        $this->registerManagers();

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectWithAttachments')->getArgument(0);
        $field = $config['fields']['attachments'];

        $this->assertInstanceOf(Reference::class, $field['type']);
        $this->assertSame(TypeRegistry::TAG.'.AttachmentConnection', (string) $field['type']);
    }

    public function testConnectionFieldHasFirstAndAfterArgs(): void
    {
        $this->registerManagers();

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectWithAttachments')->getArgument(0);
        $field = $config['fields']['attachments'];

        $this->assertArrayHasKey('first', $field['args']);
        $this->assertArrayHasKey('after', $field['args']);

        $this->assertSame('first', $field['args']['first']['name']);
        $this->assertSame(Type::int(), ($field['args']['first']['type'])());

        $this->assertSame('after', $field['args']['after']['name']);
        $this->assertSame(Type::id(), ($field['args']['after']['type'])());
    }

    public function testConnectionFieldResolvesToHandler(): void
    {
        $this->registerManagers();

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectWithAttachments')->getArgument(0);
        $handlerId = (string) $config['fields']['attachments']['resolve'];

        $this->assertSame('graphql.connection.handler.ProjectWithAttachments.attachments', $handlerId);

        $handler = $this->container->findDefinition($handlerId);
        $this->assertSame(NestedConnectionFieldHandler::class, $handler->getClass());

        $closureDefinition = $handler->getArgument(0);
        $this->assertInstanceOf(Definition::class, $closureDefinition);
        $this->assertSame([\Closure::class, 'fromCallable'], $closureDefinition->getFactory());
        $callable = $closureDefinition->getArgument(0);
        $this->assertSame(ConnectionFieldManager::class, (string) $callable[0]);
        $this->assertSame('findAttachments', $callable[1]);

        $this->assertSame(ConnectionResolver::class, (string) $handler->getArgument(1));
        $this->assertSame('%likeuntomurphy_graphql.pagination.limit%', $handler->getArgument(2));
    }

    public function testDoesNothingWithNoConnectionFieldManagers(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $this->assertFalse($this->container->hasDefinition('graphql.connection.handler.Project.attachments'));
    }

    public function testThrowsInvalidConnectionFieldExceptionForNonExistentClass(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, InvalidConnectionManager::class);

        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );

        $this->expectException(InvalidConnectionFieldException::class);
        $this->expectExceptionMessageMatches('/PaginatedResults<NonExistent>/');

        $this->compile();
    }

    public function testThrowsWhenReturnTypeIsNotGeneric(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, NonGenericConnectionManager::class);

        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );

        $this->expectException(InvalidConnectionFieldException::class);
        $this->expectExceptionMessageMatches('/must have a generic return type/');

        $this->compile();
    }

    public function testThrowsWhenGenericTypeWrapsNonObjectType(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, ScalarGenericConnectionManager::class);

        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );

        $this->expectException(InvalidConnectionFieldException::class);
        $this->expectExceptionMessageMatches('/must wrap an object type/');

        $this->compile();
    }

    public function testThrowsWhenParentTypeNotInContainer(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, ConnectionFieldManager::class);

        $this->expectException(InvalidConnectionFieldException::class);

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new QueryFieldPass());
        $container->addCompilerPass(new ConnectionFieldPass());
    }

    private function registerManagers(): void
    {
        $this->registerEntity(ProjectWithAttachments::class, ConnectionFieldManager::class);

        // Simulate the parent type created by GlobalObjectTypePass.
        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );
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
