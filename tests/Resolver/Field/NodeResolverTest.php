<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException;
use Likeuntomurphy\GraphQL\Resolver\Field\Base64NodeIdCodec;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeResolver;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\WidgetManagerStub;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\NodeResolver
 */
class NodeResolverTest extends TestCase
{
    private TypeRegistry $registry;

    protected function setUp(): void
    {
        $widgetType = new ObjectType(['name' => 'Widget', 'fields' => ['id' => Type::id()]]);

        $this->registry = new TypeRegistry(new ServiceLocator([
            'Widget' => fn () => $widgetType,
        ]));
    }

    public function testReadCallsManagerRead(): void
    {
        $widget = new \stdClass();
        $widget->id = '99';

        $manager = $this->getMockBuilder(WidgetManagerStub::class)
            ->onlyMethods(['read'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('read')
            ->with('99')
            ->willReturn($widget)
        ;

        $resolver = $this->buildResolver($manager);

        $result = $resolver(null, ['id' => base64_encode('Widget:99')]);

        $this->assertSame($widget, $result);
    }

    public function testThrowsForInvalidId(): void
    {
        $this->expectException(InvalidNodeIdArgumentException::class);

        $resolver = $this->buildResolver(new WidgetManagerStub());

        $resolver(null, ['id' => '!!!not-base64!!!']);
    }

    private function buildResolver(WidgetManagerStub $manager): NodeResolver
    {
        $managers = $this->createStub(ServiceProviderInterface::class);
        $managers->method('get')->willReturn($manager);

        return new NodeResolver(
            new NodeIdResolver($this->registry, new Base64NodeIdCodec()),
            $managers,
        );
    }
}
