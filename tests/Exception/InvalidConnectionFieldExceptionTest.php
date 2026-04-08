<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\InvalidConnectionFieldException
 */
class InvalidConnectionFieldExceptionTest extends TestCase
{
    public function testMessageContainsServiceIdAndCause(): void
    {
        $previous = new \ReflectionException('Class "App\Document\Missing" does not exist');
        $exception = new InvalidConnectionFieldException('App\Manager\ProjectManager', $previous);

        $this->assertStringContainsString('App\Manager\ProjectManager', $exception->getMessage());
        $this->assertStringContainsString('Class "App\Document\Missing" does not exist', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $previous = new \ReflectionException('test');

        $this->assertInstanceOf(\LogicException::class, new InvalidConnectionFieldException('service.id', $previous));
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new \ReflectionException('test');
        $exception = new InvalidConnectionFieldException('service.id', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
