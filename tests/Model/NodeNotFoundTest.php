<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\NodeNotFound;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\NodeNotFound
 */
class NodeNotFoundTest extends TestCase
{
    public function testIdIsStored(): void
    {
        $nodeId = base64_encode('Widget:42');

        $this->assertSame($nodeId, (new NodeNotFound($nodeId))->id);
    }
}
