<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\PageInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\PageInfo
 */
class PageInfoTest extends TestCase
{
    public function testHasPreviousPageDefaultsToFalse(): void
    {
        $this->assertFalse((new PageInfo(false, null, null))->hasPreviousPage);
    }

    public function testHasNextPageIsStored(): void
    {
        $this->assertTrue((new PageInfo(true, null, null))->hasNextPage);
        $this->assertFalse((new PageInfo(false, null, null))->hasNextPage);
    }

    public function testStartCursorIsStored(): void
    {
        $this->assertSame('abc', (new PageInfo(false, 'abc', null))->startCursor);
        $this->assertNull((new PageInfo(false, null, null))->startCursor);
    }

    public function testEndCursorIsStored(): void
    {
        $this->assertSame('xyz', (new PageInfo(false, null, 'xyz'))->endCursor);
        $this->assertNull((new PageInfo(false, null, null))->endCursor);
    }
}
