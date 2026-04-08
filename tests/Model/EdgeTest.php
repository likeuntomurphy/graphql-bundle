<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\Edge;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\Edge
 */
class EdgeTest extends TestCase
{
    public function testNodePropertyIsStored(): void
    {
        $node = new \stdClass();
        $edge = new Edge($node, 'cursor1');

        $this->assertSame($node, $edge->node);
    }

    public function testCursorPropertyIsStored(): void
    {
        $edge = new Edge(null, 'abc123');

        $this->assertSame('abc123', $edge->cursor);
    }
}
