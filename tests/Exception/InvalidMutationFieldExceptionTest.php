<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\InvalidMutationFieldException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\InvalidMutationFieldException
 */
class InvalidMutationFieldExceptionTest extends TestCase
{
    public function testMessageContainsServiceIdAndCause(): void
    {
        $previous = new \ReflectionException('Class "App\Dto\Missing" does not exist');
        $exception = new InvalidMutationFieldException('App\Manager\ProjectManager', $previous);

        $this->assertStringContainsString('App\Manager\ProjectManager', $exception->getMessage());
        $this->assertStringContainsString('Class "App\Dto\Missing" does not exist', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $previous = new \ReflectionException('test');

        $this->assertInstanceOf(\LogicException::class, new InvalidMutationFieldException('service.id', $previous));
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new \ReflectionException('test');
        $exception = new InvalidMutationFieldException('service.id', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
