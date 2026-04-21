<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionFieldHandler;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\ConnectionFieldHandler
 */
class ConnectionFieldHandlerTest extends TestCase
{
    public function testCallsManagerListAndBuildsConnection(): void
    {
        $doc = new class implements GlobalObjectInterface {
            public function getId(): string
            {
                return 'id-1';
            }
        };

        $pageInfo = new PageInfo(false, 'id-1', 'id-1');

        $manager = $this->getMockBuilder(ConnectionHandlerManagerStub::class)
            ->onlyMethods(['list'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('list')
            ->with(self::callback(fn (CursorPaginationParams $r) => 10 === $r->first))
            ->willReturn(new PaginatedResults([$doc], $pageInfo))
        ;

        $resolver = $this->buildResolver();
        $handler = new ConnectionFieldHandler($manager, $resolver);
        $info = $this->createStub(ResolveInfo::class);

        $result = $handler(null, ['first' => 10], [], $info);

        $this->assertInstanceOf(Connection::class, $result);
        $this->assertCount(1, $result->edges);
        $this->assertSame($doc, $result->edges[0]->node);
    }

    public function testPassesAfterArgDirectlyToManager(): void
    {
        $pageInfo = new PageInfo(false, null, null);

        $manager = $this->getMockBuilder(ConnectionHandlerManagerStub::class)
            ->onlyMethods(['list'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('list')
            ->with(self::callback(fn (CursorPaginationParams $r) => 'cursor123' === $r->after))
            ->willReturn(new PaginatedResults([], $pageInfo))
        ;

        $resolver = $this->buildResolver();
        $handler = new ConnectionFieldHandler($manager, $resolver);
        $info = $this->createStub(ResolveInfo::class);

        $handler(null, ['after' => 'cursor123'], [], $info);
    }

    private function buildResolver(): ConnectionResolver
    {
        return new ConnectionResolver();
    }
}

/**
 * @internal
 */
class ConnectionHandlerManagerStub implements GlobalObjectManagerInterface, ListableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return \stdClass::class;
    }

    /** @return PaginatedResults<GlobalObjectInterface> */
    public function list(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
