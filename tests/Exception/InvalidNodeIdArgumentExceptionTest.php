<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\InvalidNodeIdArgumentException
 */
class InvalidNodeIdArgumentExceptionTest extends TestCase
{
    public function testMessageDescribesInvalidArgument(): void
    {
        $exception = new InvalidNodeIdArgumentException();

        $this->assertStringContainsString('id', $exception->getMessage());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new InvalidNodeIdArgumentException());
    }
}
