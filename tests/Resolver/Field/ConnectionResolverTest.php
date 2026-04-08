<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver
 */
class ConnectionResolverTest extends TestCase
{
    private ConnectionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ConnectionResolver();
    }

    public function testReturnsConnectionFromDocuments(): void
    {
        $doc1 = $this->createDocument('id-1');
        $doc2 = $this->createDocument('id-2');

        $pageInfo = new PageInfo(
            hasNextPage: true,
            startCursor: 'id-1',
            endCursor: 'id-2',
        );

        $result = $this->resolver->resolve([$doc1, $doc2], $pageInfo);

        $this->assertInstanceOf(Connection::class, $result);
        $this->assertCount(2, $result->edges);
        $this->assertSame($doc1, $result->edges[0]->node);
        $this->assertSame('id-1', $result->edges[0]->cursor);
        $this->assertSame($doc2, $result->edges[1]->node);
        $this->assertSame('id-2', $result->edges[1]->cursor);
        $this->assertSame($pageInfo, $result->pageInfo);
    }

    public function testEmptyDocumentsProducesEmptyEdges(): void
    {
        $pageInfo = new PageInfo(false, null, null);

        $result = $this->resolver->resolve([], $pageInfo);

        $this->assertInstanceOf(Connection::class, $result);
        $this->assertEmpty($result->edges);
        $this->assertSame($pageInfo, $result->pageInfo);
    }

    private function createDocument(string $id): GlobalObjectInterface
    {
        return new class($id) implements GlobalObjectInterface {
            public function __construct(
                private string $id,
            ) {
            }

            public function getId(): string
            {
                return $this->id;
            }
        };
    }
}
