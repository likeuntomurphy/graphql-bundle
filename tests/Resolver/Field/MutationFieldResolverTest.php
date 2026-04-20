<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\Resolver\Field\Base64NodeIdCodec;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\Color;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\ProjectValidationGroup;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\StubDocument;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\WidgetManagerStub;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver
 */
class MutationFieldResolverTest extends TestCase
{
    private TypeRegistry $registry;

    protected function setUp(): void
    {
        $widgetType = new ObjectType(['name' => 'Widget', 'fields' => ['id' => Type::id()]]);

        $this->registry = new TypeRegistry(new ServiceLocator([
            'Widget' => fn () => $widgetType,
        ]));
    }

    public function testCreateCallsManagerCreate(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->with(self::callback(
                fn (object $document): bool => 'new-widget' === ($document->name ?? null),
            ))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolver($manager);

        $result = $resolver->resolve('create', 'Widget', ['name' => 'new-widget']);

        $this->assertSame('new-widget', $result->name ?? null);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testValidatorReceivesGroupsFromArgs(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with(self::isInstanceOf(StubDocument::class), null, ['Default'])
            ->willReturn(new ConstraintViolationList())
        ;

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->method('create')->willReturnArgument(0);

        $resolver = $this->buildResolver($manager, validator: $validator);

        $resolver->resolve('create', 'Widget', ['name' => 'foo', 'validationGroups' => [ProjectValidationGroup::Default]]);
    }

    public function testUpdateCallsManagerUpdateWithMergedDocument(): void
    {
        $existing = new StubDocument();
        $existing->name = 'old';

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('read')
            ->with('42')
            ->willReturn($existing)
        ;

        $manager->expects(self::once())
            ->method('update')
            ->with(self::identicalTo($existing))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolver($manager);

        $result = $resolver->resolve('update', 'Widget', ['id' => base64_encode('Widget:42'), 'name' => 'new']);

        $this->assertSame('new', $existing->name);
        $this->assertSame($existing, $result);
    }

    public function testDeleteCallsManagerDelete(): void
    {
        $existing = new \stdClass();

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())->method('read')->with('42')->willReturn($existing);
        $manager->expects(self::once())->method('delete')->with(self::identicalTo($existing))->willReturnArgument(0);

        $resolver = $this->buildResolver($manager);

        $result = $resolver->resolve('delete', 'Widget', ['id' => base64_encode('Widget:42')]);

        $this->assertSame($existing, $result);
    }

    public function testReturnsValidationErrorListWhenValidatorReturnsViolationsOnCreate(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('must not be blank', '', [], null, 'name', null),
        ]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->expects(self::never())->method('create');

        $resolver = $this->buildResolver($manager, validator: $validator);

        $result = $resolver->resolve('create', 'Widget', ['name' => '']);

        $this->assertInstanceOf(ValidationErrorList::class, $result);
        $this->assertCount(1, $result->errors);
        $this->assertSame('name', $result->errors[0]->path);
    }

    public function testReturnsValidationErrorListWhenValidatorReturnsViolationsOnUpdate(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('too short', '', [], null, 'name', null),
        ]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        $existing = new \stdClass();

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->method('read')->willReturn($existing);
        $manager->expects(self::never())->method('update');

        $resolver = $this->buildResolver($manager, validator: $validator);

        $result = $resolver->resolve('update', 'Widget', ['id' => base64_encode('Widget:42'), 'name' => 'x']);

        $this->assertInstanceOf(ValidationErrorList::class, $result);
        $this->assertCount(1, $result->errors);
    }

    public function testCreateResolvesRelationToReferencedObject(): void
    {
        $project = new \stdClass();
        $project->id = '99';

        $projectManager = $this->createStub(GlobalObjectManagerInterface::class);
        $projectManager->method('read')->willReturn($project);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->with(self::callback(
                fn (object $document): bool => $project === ($document->project ?? null),
            ))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolverWithRelationManager($manager, $projectManager);

        $resolver->resolve(
            'create',
            'Widget',
            ['projectId' => base64_encode('Widget:99'), 'name' => 'foo'],
            ['projectId' => ['property' => 'project', 'target' => 'Project']],
        );
    }

    public function testCreateReturnsValidationErrorWhenRelationTargetMissing(): void
    {
        $projectManager = $this->createStub(GlobalObjectManagerInterface::class);
        $projectManager->method('read')->willReturn(null);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->expects(self::never())->method('create');

        $resolver = $this->buildResolverWithRelationManager($manager, $projectManager);

        $result = $resolver->resolve(
            'create',
            'Widget',
            ['projectId' => base64_encode('Widget:99'), 'name' => 'foo'],
            ['projectId' => ['property' => 'project', 'target' => 'Project']],
        );

        $this->assertInstanceOf(ValidationErrorList::class, $result);
        $this->assertCount(1, $result->errors);
        $this->assertSame('project', $result->errors[0]->path);
    }

    public function testUpdateReturnsNodeNotFoundWhenDocumentMissing(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->method('read')->willReturn(null);
        $manager->expects(self::never())->method('update');

        $resolver = $this->buildResolver($manager);
        $nodeId = base64_encode('Widget:42');

        $result = $resolver->resolve('update', 'Widget', ['id' => $nodeId, 'name' => 'updated']);

        $this->assertInstanceOf(NodeNotFound::class, $result);
        $this->assertSame($nodeId, $result->id);
    }

    public function testDeleteReturnsNodeNotFoundWhenDocumentMissing(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->method('read')->willReturn(null);
        $manager->expects(self::never())->method('delete');

        $resolver = $this->buildResolver($manager);
        $nodeId = base64_encode('Widget:42');

        $result = $resolver->resolve('delete', 'Widget', ['id' => $nodeId]);

        $this->assertInstanceOf(NodeNotFound::class, $result);
        $this->assertSame($nodeId, $result->id);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFlattensBackedEnumToValue(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->expects(self::once())
            ->method('create')
            ->with(self::callback(
                fn (object $document): bool => 'red' === ($document->color ?? null),
            ))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolver($manager);

        $resolver->resolve('create', 'Widget', ['color' => Color::Red]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFlattensUnitEnumToName(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->expects(self::once())
            ->method('create')
            ->with(self::callback(
                fn (object $document): bool => 'Default' === ($document->group ?? null),
            ))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolver($manager);

        $resolver->resolve('create', 'Widget', ['group' => ProjectValidationGroup::Default]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFlattensEnumsInNestedArrays(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;
        $manager->expects(self::once())
            ->method('create')
            ->with(self::callback(
                fn (object $document): bool => ['color' => 'green'] === ($document->nested ?? null),
            ))
            ->willReturnArgument(0)
        ;

        $resolver = $this->buildResolver($manager);

        $resolver->resolve('create', 'Widget', ['nested' => ['color' => Color::Green]]);
    }

    public function testThrowsOnUnknownMethod(): void
    {
        $resolver = $this->buildResolver(new WidgetManagerStub());

        $this->expectException(UnknownMutationMethodException::class);
        $this->expectExceptionMessage('Unknown mutation method "archive".');

        $resolver->resolve('archive', 'Widget', []);
    }

    private function buildResolver(
        WidgetManagerStub $manager,
        ?DenormalizerInterface $denormalizer = null,
        ?ValidatorInterface $validator = null,
    ): MutationFieldResolver {
        return new MutationFieldResolver(
            new NodeIdResolver($this->registry, new Base64NodeIdCodec()),
            $denormalizer ?? new ObjectNormalizer(),
            $validator ?? Validation::createValidator(),
            $this->managerProvider($manager),
            $this->managerProvider($manager),
            $this->managerProvider($manager),
            $this->managerProvider($manager),
        );
    }

    /** @return ServiceProviderInterface<WidgetManagerStub> */
    private function managerProvider(WidgetManagerStub $manager): ServiceProviderInterface
    {
        $provider = $this->createStub(ServiceProviderInterface::class);
        $provider->method('get')->willReturn($manager);

        return $provider;
    }

    private function buildResolverWithRelationManager(
        WidgetManagerStub $manager,
        GlobalObjectManagerInterface $relationManager,
    ): MutationFieldResolver {
        $relations = $this->createStub(ServiceProviderInterface::class);
        $relations->method('get')->willReturn($relationManager);

        return new MutationFieldResolver(
            new NodeIdResolver($this->registry, new Base64NodeIdCodec()),
            new ObjectNormalizer(),
            Validation::createValidator(),
            $this->managerProvider($manager),
            $this->managerProvider($manager),
            $this->managerProvider($manager),
            $relations,
        );
    }
}
