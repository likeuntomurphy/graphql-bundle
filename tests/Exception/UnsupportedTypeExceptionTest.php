<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\UnsupportedTypeException
 */
class UnsupportedTypeExceptionTest extends TestCase
{
    public function testMessageContainsTypeAndPropertyName(): void
    {
        $type = new class implements \Stringable {
            public function __toString(): string
            {
                return 'array';
            }
        };

        $exception = new UnsupportedTypeException($type, 'tags');

        $this->assertSame('Unsupported type "array" for property "tags".', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $type = new class implements \Stringable {
            public function __toString(): string
            {
                return 'mixed';
            }
        };

        $this->assertInstanceOf(\LogicException::class, new UnsupportedTypeException($type, 'field'));
    }
}
