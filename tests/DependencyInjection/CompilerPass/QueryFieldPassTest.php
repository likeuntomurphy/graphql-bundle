<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass;
use Likeuntomurphy\GraphQL\Exception\InvalidQueryFieldException;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Report;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\InvalidManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ListableManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\NonListableManager;
use Likeuntomurphy\GraphQL\Type\Query;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass
 */
class QueryFieldPassTest extends AbstractCompilerPassTestCase
{
    public function testCreatesConnectionTypesForManager(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.ProjectEdge');
        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.ProjectConnection');
    }

    public function testCreatesQueryFieldWhenListMethodExists(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $this->assertContainerBuilderHasService('graphql.query.field.projects');
    }

    public function testDoesNotCreateQueryFieldWithoutListMethod(): void
    {
        $this->registerEntity(Report::class, NonListableManager::class);

        $this->compile();

        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.ReportEdge');
        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.ReportConnection');
        $this->assertContainerBuilderNotHasService('graphql.query.field.reports');
    }

    public function testQueryFieldIsTaggedForQueryType(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'graphql.query.field.projects',
            Query::FIELD_TAG,
        );
    }

    public function testQueryFieldHasCorrectName(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $config = $this->container->findDefinition('graphql.query.field.projects')->getArgument(0);

        $this->assertSame('projects', $config['name']);
    }

    public function testQueryFieldTypeIsConnectionReference(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $config = $this->container->findDefinition('graphql.query.field.projects')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['type']);
        $this->assertSame(TypeRegistry::TAG.'.ProjectConnection', (string) $config['type']);
    }

    public function testQueryFieldResolvesToHandler(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $config = $this->container->findDefinition('graphql.query.field.projects')->getArgument(0);
        $this->assertSame('graphql.connection.handler.Query.projects', (string) $config['resolve']);

        $handler = $this->container->findDefinition('graphql.connection.handler.Query.projects');
        $this->assertSame(ConnectionFieldHandler::class, $handler->getClass());
        $this->assertSame(ListableManager::class, (string) $handler->getArgument(0));
        $this->assertSame(ConnectionResolver::class, (string) $handler->getArgument(1));
    }

    public function testQueryFieldHasFirstAndAfterArgs(): void
    {
        $this->registerEntity(Project::class, ListableManager::class);

        $this->compile();

        $config = $this->container->findDefinition('graphql.query.field.projects')->getArgument(0);

        $this->assertArrayHasKey('first', $config['args']);
        $this->assertArrayHasKey('after', $config['args']);

        $first = $config['args']['first'];
        $this->assertSame('first', $first['name']);
        $this->assertSame(Type::int(), ($first['type'])());

        $after = $config['args']['after'];
        $this->assertSame('after', $after['name']);
        $this->assertSame(Type::id(), ($after['type'])());
    }

    public function testThrowsInvalidQueryFieldExceptionForNonExistentClass(): void
    {
        $this->container->setDefinition(InvalidManager::class, new Definition(InvalidManager::class));
        $this->container->setDefinition(
            'NonExistent',
            (new Definition('NonExistent'))->addResourceTag(GlobalObject::RESOURCE_TAG, ['manager' => InvalidManager::class]),
        );

        $this->expectException(InvalidQueryFieldException::class);
        $this->expectExceptionMessageMatches('/NonExistent/');

        $this->compile();
    }

    public function testDoesNothingWithNoManagers(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService('graphql.query.field.projects');
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new QueryFieldPass());
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
