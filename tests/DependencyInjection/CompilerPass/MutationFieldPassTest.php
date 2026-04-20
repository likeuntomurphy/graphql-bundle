<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\MutationFieldPass;
use Likeuntomurphy\GraphQL\Exception\InvalidMutationFieldException;
use Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\CreateOnlyManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\CrudManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\DeleteOnlyManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ExcludedDeleteManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\IdFieldManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\InvalidManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\NullableManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\OrderManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ReadonlyManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\TicketManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\UnsupportedManager;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\ValidatableCreateManager;
use Likeuntomurphy\GraphQL\Type\Mutation;
use Likeuntomurphy\GraphQL\Type\ValidationErrorList;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\MutationFieldPass
 */
class MutationFieldPassTest extends AbstractCompilerPassTestCase
{
    public function testRegistersMutationResultUnionType(): void
    {
        $this->registerManager(CrudManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            TypeRegistry::TAG.'.ProjectMutationResult',
            TypeRegistry::TAG,
            ['name' => 'ProjectMutationResult'],
        );
    }

    public function testMutationResultUnionContainsObjectTypeAndValidationErrorList(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectMutationResult')->getArgument(0);

        $this->assertSame(TypeRegistry::TAG.'.Project', (string) $config['types'][0]);
        $this->assertSame(ValidationErrorList::class, (string) $config['types'][1]);
    }

    public function testRegistersCreateMutationField(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertContainerBuilderHasService('graphql.mutation.field.createProject');
    }

    public function testCreateMutationFieldIsTagged(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'graphql.mutation.field.createProject',
            Mutation::FIELD_TAG,
        );
    }

    public function testCreateMutationFieldHasPayloadArgs(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createProject')->getArgument(0);

        $this->assertArrayHasKey('name', $config['args']);
        $this->assertArrayNotHasKey('id', $config['args']);
    }

    public function testCreateMutationFieldHasCorrectName(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createProject')->getArgument(0);

        $this->assertSame('createProject', $config['name']);
    }

    public function testMutationFieldResolvesToHandler(): void
    {
        $this->registerManager(CrudManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $createConfig = $this->container->findDefinition('graphql.mutation.field.createProject')->getArgument(0);
        $this->assertSame('graphql.mutation.handler.createProject', (string) $createConfig['resolve']);
        $this->assertArrayNotHasKey('method', $createConfig);
        $this->assertArrayNotHasKey('typeName', $createConfig);

        $createHandler = $this->container->findDefinition('graphql.mutation.handler.createProject');
        $this->assertSame(MutationFieldHandler::class, $createHandler->getClass());
        $this->assertSame('create', $createHandler->getArgument(0));
        $this->assertSame('Project', $createHandler->getArgument(1));
        $this->assertSame(MutationFieldResolver::class, (string) $createHandler->getArgument(2));

        $deleteHandler = $this->container->findDefinition('graphql.mutation.handler.deleteProject');
        $this->assertSame('delete', $deleteHandler->getArgument(0));
        $this->assertSame('Project', $deleteHandler->getArgument(1));
    }

    public function testUpdateMutationFieldHasIdAndPayloadArgs(): void
    {
        $this->registerManager(CrudManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.updateProject')->getArgument(0);

        $this->assertArrayHasKey('id', $config['args']);
        $this->assertArrayHasKey('name', $config['args']);
    }

    public function testDeleteMutationFieldHasOnlyIdArg(): void
    {
        $this->registerManager(DeleteOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.deleteProject')->getArgument(0);

        $this->assertArrayHasKey('id', $config['args']);
        $this->assertCount(1, $config['args']);
    }

    public function testSkipsMethodsNotOnManager(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertFalse($this->container->hasDefinition('graphql.mutation.field.updateProject'));
        $this->assertFalse($this->container->hasDefinition('graphql.mutation.field.deleteProject'));
    }

    public function testSkipsUnimplementedInterfaces(): void
    {
        $this->registerManager(ExcludedDeleteManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertTrue($this->container->hasDefinition('graphql.mutation.field.createProject'));
        $this->assertFalse($this->container->hasDefinition('graphql.mutation.field.deleteProject'));
    }

    public function testValidationGroupsArgWhenManagerIsValidatable(): void
    {
        $this->registerManager(ValidatableCreateManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createProject')->getArgument(0);

        $this->assertArrayHasKey('validationGroups', $config['args']);
        $this->assertSame(TypeRegistry::TAG.'.ProjectValidationGroup.non_null.list_of', (string) $config['args']['validationGroups']['type']);
    }

    public function testValidationGroupEnumIsRegisteredAsType(): void
    {
        $this->registerManager(ValidatableCreateManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $this->assertContainerBuilderHasService(TypeRegistry::TAG.'.ProjectValidationGroup');

        $definition = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectValidationGroup');

        $this->assertSame(PhpEnumType::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
    }

    public function testNoValidationGroupsArgWhenManagerIsNotValidatable(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createProject')->getArgument(0);

        $this->assertArrayNotHasKey('validationGroups', $config['args']);
    }

    public function testNestedObjectCreatesInputType(): void
    {
        $this->registerManager(OrderManager::class);
        $this->registerObjectType('Order');

        $this->compile();

        $this->assertTrue($this->container->hasDefinition(TypeRegistry::TAG.'.AddressInput'));

        $definition = $this->container->findDefinition(TypeRegistry::TAG.'.AddressInput');

        $this->assertSame(InputObjectType::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
    }

    public function testInputTypeNameHasSuffix(): void
    {
        $this->registerManager(OrderManager::class);
        $this->registerObjectType('Order');

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.AddressInput')->getArgument(0);

        $this->assertSame('AddressInput', $config['name']);
    }

    public function testEnumPropertyResolvesToTypeReference(): void
    {
        $this->registerManager(TicketManager::class);
        $this->registerObjectType('Ticket');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createTicket')->getArgument(0);

        $this->assertSame(TypeRegistry::TAG.'.Priority.non_null', (string) $config['args']['priority']['type']);
    }

    public function testReadonlyPropertyIsSkipped(): void
    {
        $this->registerManager(ReadonlyManager::class);
        $this->registerObjectType('ReadonlyDocument');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createReadonlyDocument')->getArgument(0);

        $this->assertArrayHasKey('label', $config['args']);
        $this->assertArrayNotHasKey('ref', $config['args']);
    }

    public function testNullablePropertyIsNotNonNull(): void
    {
        $this->registerManager(NullableManager::class);
        $this->registerObjectType('NullableDocument');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createNullableDocument')->getArgument(0);
        $ref = $config['args']['body']['type'];

        $this->assertSame(TypeRegistry::TAG.'.'.Type::STRING, (string) $ref);
    }

    public function testThrowsForUnsupportedType(): void
    {
        $this->registerManager(UnsupportedManager::class);
        $this->registerObjectType('UnsupportedDocument');

        $this->expectException(UnsupportedTypeException::class);

        $this->compile();
    }

    public function testThrowsInvalidMutationFieldExceptionForNonExistentClass(): void
    {
        $this->registerManager(InvalidManager::class);
        $this->registerObjectType('NonExistent');

        $this->expectException(InvalidMutationFieldException::class);
        $this->expectExceptionMessageMatches('/InvalidManager/');

        $this->compile();
    }

    public function testDoesNothingWithNoManagers(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService('graphql.mutation.field.createProject');
    }

    public function testMutationResultHasResolveTypeReference(): void
    {
        $this->registerManager(CreateOnlyManager::class);
        $this->registerObjectType('Project');

        $this->compile();

        $config = $this->container->findDefinition(TypeRegistry::TAG.'.ProjectMutationResult')->getArgument(0);

        $this->assertInstanceOf(Reference::class, $config['resolveType']);
        $this->assertSame(ObjectTypeResolver::class, (string) $config['resolveType']);
    }

    public function testIdFieldAttributeResolvesToIdType(): void
    {
        $this->registerManager(IdFieldManager::class);
        $this->registerObjectType('Attachment');

        $this->compile();

        $config = $this->container->findDefinition('graphql.mutation.field.createAttachment')->getArgument(0);

        $this->assertSame(TypeRegistry::TAG.'.ID.non_null', (string) $config['args']['projectId']['type']);
        $this->assertSame(TypeRegistry::TAG.'.String.non_null', (string) $config['args']['url']['type']);
    }

    public function testRelationMapPassedToHandler(): void
    {
        $this->registerManager(IdFieldManager::class);
        $this->registerObjectType('Attachment');

        $this->compile();

        $handler = $this->container->findDefinition('graphql.mutation.handler.createAttachment');

        $this->assertSame(['projectId' => ['property' => 'project', 'target' => 'Project']], $handler->getArgument(3));
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MutationFieldPass());
    }

    /**
     * @param class-string<GlobalObjectManagerInterface> $managerClass
     */
    private function registerManager(string $managerClass): void
    {
        $this->container->setDefinition(
            $managerClass,
            (new Definition($managerClass))->addTag(GlobalObjectManagerInterface::TAG),
        );
    }

    private function registerObjectType(string $typeName): void
    {
        $this->container->setDefinition(
            TypeRegistry::TAG.'.'.$typeName,
            new Definition(ObjectType::class),
        );
    }
}
