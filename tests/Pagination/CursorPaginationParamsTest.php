<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Pagination;

use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams
 */
class CursorPaginationParamsTest extends TestCase
{
    public function testDefaultsToNullValues(): void
    {
        $params = new CursorPaginationParams();

        $this->assertNull($params->first);
        $this->assertNull($params->after);
    }

    public function testAcceptsConstructorArgs(): void
    {
        $params = new CursorPaginationParams(25, '6579e4a1b3d2c1a0f8e7d6c5');

        $this->assertSame(25, $params->first);
        $this->assertSame('6579e4a1b3d2c1a0f8e7d6c5', $params->after);
    }
}
