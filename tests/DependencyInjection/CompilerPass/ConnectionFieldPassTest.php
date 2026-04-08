<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\ConnectionFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass;
use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler;
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
        $this->container->setDefinition(
            ListableManager::class,
            (new Definition(ListableManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->compile();

        $this->assertFalse($this->container->hasDefinition('graphql.connection.handler.Project.attachments'));
    }

    public function testThrowsInvalidConnectionFieldExceptionForNonExistentClass(): void
    {
        $this->container->setDefinition(
            InvalidConnectionManager::class,
            (new Definition(InvalidConnectionManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );

        $this->expectException(InvalidConnectionFieldException::class);
        $this->expectExceptionMessageMatches('/InvalidConnectionManager/');

        $this->compile();
    }

    public function testThrowsWhenReturnTypeIsNotGeneric(): void
    {
        $this->container->setDefinition(
            NonGenericConnectionManager::class,
            (new Definition(NonGenericConnectionManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

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
        $this->container->setDefinition(
            ScalarGenericConnectionManager::class,
            (new Definition(ScalarGenericConnectionManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

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
        $this->container->setDefinition(
            ConnectionFieldManager::class,
            (new Definition(ConnectionFieldManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

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
        $this->container->setDefinition(
            ConnectionFieldManager::class,
            (new Definition(ConnectionFieldManager::class))->addTag(GlobalObjectManagerInterface::TAG),
        );

        // Simulate the parent type created by GlobalObjectTypePass.
        $this->container->setDefinition(
            TypeRegistry::TAG.'.ProjectWithAttachments',
            new Definition(ObjectType::class, [['name' => 'ProjectWithAttachments', 'fields' => []]]),
        );
    }
}
