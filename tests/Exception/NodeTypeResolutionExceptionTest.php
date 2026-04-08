<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Exception\ObjectTypeResolutionException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\ObjectTypeResolutionException
 */
class NodeTypeResolutionExceptionTest extends TestCase
{
    public function testMessageContainsTypeName(): void
    {
        $exception = new ObjectTypeResolutionException(Type::string());

        $this->assertSame('Cannot resolve an object type to String', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $this->assertInstanceOf(\LogicException::class, new ObjectTypeResolutionException(Type::string()));
    }
}
