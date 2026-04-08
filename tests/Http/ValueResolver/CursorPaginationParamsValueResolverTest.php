<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Http\ValueResolver;

use Likeuntomurphy\GraphQL\Http\ValueResolver\CursorPaginationParamsValueResolver;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Http\ValueResolver\CursorPaginationParamsValueResolver
 */
class CursorPaginationParamsValueResolverTest extends TestCase
{
    public function testYieldsNothingForNonMatchingType(): void
    {
        $resolver = new CursorPaginationParamsValueResolver(CursorPaginationParams::LIMIT);
        $argument = new ArgumentMetadata('request', \stdClass::class, false, false, null);

        $result = iterator_to_array($resolver->resolve(new Request(), $argument));

        $this->assertEmpty($result);
    }

    public function testYieldsParamsWithDefaults(): void
    {
        $resolver = new CursorPaginationParamsValueResolver(50);
        $argument = new ArgumentMetadata('request', CursorPaginationParams::class, false, false, null);

        $result = iterator_to_array($resolver->resolve(new Request(), $argument));

        $this->assertCount(1, $result);
        $this->assertSame(50, $result[0]->getFirst());
        $this->assertSame(CursorPaginationParams::MIN_ID, $result[0]->getAfter());
    }

    public function testReadsFirstAndAfterFromQueryString(): void
    {
        $resolver = new CursorPaginationParamsValueResolver(CursorPaginationParams::LIMIT);
        $argument = new ArgumentMetadata('request', CursorPaginationParams::class, false, false, null);
        $request = new Request(['first' => 10, 'after' => '6579e4a1b3d2c1a0f8e7d6c5']);

        $result = iterator_to_array($resolver->resolve($request, $argument));

        $this->assertSame(10, $result[0]->getFirst());
        $this->assertSame('6579e4a1b3d2c1a0f8e7d6c5', $result[0]->getAfter());
    }

    public function testCapsFirstToConfiguredLimit(): void
    {
        $resolver = new CursorPaginationParamsValueResolver(25);
        $argument = new ArgumentMetadata('request', CursorPaginationParams::class, false, false, null);
        $request = new Request(['first' => 999]);

        $result = iterator_to_array($resolver->resolve($request, $argument));

        $this->assertSame(25, $result[0]->getFirst());
    }
}
