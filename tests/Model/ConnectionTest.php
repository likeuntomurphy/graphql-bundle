<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Model\Edge;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\Connection
 */
class ConnectionTest extends TestCase
{
    public function testEdgesPropertyIsStored(): void
    {
        $edges = [new Edge('node', 'cursor1')];
        $pageInfo = new PageInfo(false, null, null);
        $connection = new Connection($edges, $pageInfo);

        $this->assertSame($edges, $connection->edges);
    }

    public function testPageInfoPropertyIsStored(): void
    {
        $pageInfo = new PageInfo(false, 'start', 'end');
        $connection = new Connection([], $pageInfo);

        $this->assertSame($pageInfo, $connection->pageInfo);
    }
}
