<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException;
use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use Likeuntomurphy\GraphQL\Resolver\Field\Base64NodeIdCodec;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\Color;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\ProjectValidationGroup;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\WidgetManagerStub;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
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
        $dto = new \stdClass();
        $dto->name = 'new-widget';

        $createdWidget = new \stdClass();
        $createdWidget->id = '1';

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn($dto);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->with($dto, self::isInstanceOf(\stdClass::class), [])
            ->willReturn($createdWidget)
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $result = $resolver->resolve('create', 'Widget', ['name' => 'new-widget']);

        $this->assertSame($createdWidget, $result);
    }

    public function testCreatePassesValidationGroups(): void
    {
        $dto = new \stdClass();
        $createdWidget = new \stdClass();

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn($dto);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->with($dto, self::isInstanceOf(\stdClass::class), [ProjectValidationGroup::Default])
            ->willReturn($createdWidget)
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $result = $resolver->resolve('create', 'Widget', ['name' => 'foo', 'validationGroups' => [ProjectValidationGroup::Default]]);

        $this->assertSame($createdWidget, $result);
    }

    public function testUpdateCallsManagerUpdate(): void
    {
        $existingWidget = new \stdClass();
        $existingWidget->id = '42';

        $dto = new \stdClass();
        $dto->name = 'updated';

        $updatedWidget = new \stdClass();
        $updatedWidget->id = '42';
        $updatedWidget->name = 'updated';

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn($dto);

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('read')
            ->with('42')
            ->willReturn($existingWidget)
        ;

        $manager->expects(self::once())
            ->method('update')
            ->with($dto, $existingWidget, [])
            ->willReturn($updatedWidget)
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $result = $resolver->resolve('update', 'Widget', ['id' => base64_encode('Widget:42'), 'name' => 'updated']);

        $this->assertSame($updatedWidget, $result);
    }

    public function testDeleteCallsManagerDelete(): void
    {
        $existingWidget = new \stdClass();
        $existingWidget->id = '42';

        $deletedWidget = new \stdClass();

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('read')
            ->with('42')
            ->willReturn($existingWidget)
        ;

        $manager->expects(self::once())
            ->method('delete')
            ->with($existingWidget)
            ->willReturn($deletedWidget)
        ;

        $resolver = $this->buildResolver($manager);

        $result = $resolver->resolve('delete', 'Widget', ['id' => base64_encode('Widget:42')]);

        $this->assertSame($deletedWidget, $result);
    }

    public function testReturnsValidationErrorListOnCreateFailure(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('must not be blank', '', [], null, 'name', null),
        ]);

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn(new \stdClass());

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->willThrowException(new ValidationFailedException('', $violations))
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $result = $resolver->resolve('create', 'Widget', ['name' => '']);

        $this->assertInstanceOf(ValidationErrorList::class, $result);
        $this->assertCount(1, $result->errors);
        $this->assertSame('name', $result->errors[0]->path);
        $this->assertSame('must not be blank', $result->errors[0]->message);
    }

    public function testReturnsValidationErrorListOnUpdateFailure(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('too short', '', [], null, 'name', null),
        ]);

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn(new \stdClass());

        $existing = new \stdClass();

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->method('read')->willReturn($existing);

        $manager->expects(self::once())
            ->method('update')
            ->willThrowException(new ValidationFailedException('', $violations))
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $result = $resolver->resolve('update', 'Widget', ['id' => base64_encode('Widget:42'), 'name' => 'x']);

        $this->assertInstanceOf(ValidationErrorList::class, $result);
        $this->assertCount(1, $result->errors);
        $this->assertSame('name', $result->errors[0]->path);
        $this->assertSame('too short', $result->errors[0]->message);
    }

    public function testCreateDecodesIdFields(): void
    {
        $dto = new \stdClass();
        $createdWidget = new \stdClass();

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            function (array $data) use ($dto) {
                $dto->projectId = $data['projectId'];

                return $dto;
            },
        );

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('create')
            ->willReturn($createdWidget)
        ;

        $resolver = $this->buildResolver($manager, $denormalizer);

        $resolver->resolve('create', 'Widget', ['projectId' => base64_encode('Widget:99'), 'name' => 'foo'], ['projectId']);

        $this->assertSame('99', $dto->projectId);
    }

    public function testUpdateReturnsNodeNotFoundWhenDocumentMissing(): void
    {
        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('read')
            ->with('42')
            ->willReturn(null)
        ;

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

        $manager->expects(self::once())
            ->method('read')
            ->with('42')
            ->willReturn(null)
        ;

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
        $dto = new \stdClass();
        $createdWidget = new \stdClass();

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            function (array $data) use ($dto) {
                $dto->color = $data['color'];

                return $dto;
            },
        );

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->method('create')->willReturn($createdWidget);

        $resolver = $this->buildResolver($manager, $denormalizer);

        $resolver->resolve('create', 'Widget', ['color' => Color::Red]);

        $this->assertSame('red', $dto->color);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFlattensUnitEnumToName(): void
    {
        $dto = new \stdClass();
        $createdWidget = new \stdClass();

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            function (array $data) use ($dto) {
                $dto->group = $data['group'];

                return $dto;
            },
        );

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->method('create')->willReturn($createdWidget);

        $resolver = $this->buildResolver($manager, $denormalizer);

        $resolver->resolve('create', 'Widget', ['group' => ProjectValidationGroup::Default]);

        $this->assertSame('Default', $dto->group);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFlattensEnumsInNestedArrays(): void
    {
        $dto = new \stdClass();
        $createdWidget = new \stdClass();

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            function (array $data) use ($dto) {
                $dto->nested = $data['nested'];

                return $dto;
            },
        );

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read', 'create', 'update', 'delete'])
            ->getMock()
        ;

        $manager->method('create')->willReturn($createdWidget);

        $resolver = $this->buildResolver($manager, $denormalizer);

        $resolver->resolve('create', 'Widget', ['nested' => ['color' => Color::Green]]);

        $this->assertSame(['color' => 'green'], $dto->nested);
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
    ): MutationFieldResolver {
        return new MutationFieldResolver(
            new NodeIdResolver($this->registry, new Base64NodeIdCodec()),
            $denormalizer ?? $this->createStub(DenormalizerInterface::class),
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
}
