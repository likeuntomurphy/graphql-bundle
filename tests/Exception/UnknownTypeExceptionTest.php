<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\UnknownTypeException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\UnknownTypeException
 */
class UnknownTypeExceptionTest extends TestCase
{
    public function testMessageContainsTypeId(): void
    {
        $exception = new UnknownTypeException('Widget');

        $this->assertSame('Unknown type: Widget', $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new UnknownTypeException('Widget'));
    }
}
