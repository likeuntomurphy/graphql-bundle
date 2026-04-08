<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\InvalidQueryFieldException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\InvalidQueryFieldException
 */
class InvalidQueryFieldExceptionTest extends TestCase
{
    public function testMessageContainsServiceIdAndCause(): void
    {
        $previous = new \ReflectionException('Class "App\Document\Missing" does not exist');
        $exception = new InvalidQueryFieldException('App\Manager\ProjectManager', $previous);

        $this->assertStringContainsString('App\Manager\ProjectManager', $exception->getMessage());
        $this->assertStringContainsString('Class "App\Document\Missing" does not exist', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $previous = new \ReflectionException('test');

        $this->assertInstanceOf(\LogicException::class, new InvalidQueryFieldException('service.id', $previous));
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new \ReflectionException('test');
        $exception = new InvalidQueryFieldException('service.id', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
