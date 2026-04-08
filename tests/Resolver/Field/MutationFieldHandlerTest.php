<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\MutationFieldHandler
 */
class MutationFieldHandlerTest extends TestCase
{
    public function testDelegatesToResolver(): void
    {
        $expected = new \stdClass();

        $resolver = $this->createMock(MutationFieldResolver::class);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with('create', 'Widget', ['name' => 'foo'])
            ->willReturn($expected)
        ;

        $handler = new MutationFieldHandler('create', 'Widget', $resolver);
        $info = $this->createStub(ResolveInfo::class);

        $result = $handler(null, ['name' => 'foo'], [], $info);

        $this->assertSame($expected, $result);
    }
}
