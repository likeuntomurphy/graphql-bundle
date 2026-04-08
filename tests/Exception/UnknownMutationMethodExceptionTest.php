<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\UnknownMutationMethodException
 */
class UnknownMutationMethodExceptionTest extends TestCase
{
    public function testMessageContainsMethodName(): void
    {
        $exception = new UnknownMutationMethodException('archive');

        $this->assertStringContainsString('archive', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $this->assertInstanceOf(\LogicException::class, new UnknownMutationMethodException('archive'));
    }
}
