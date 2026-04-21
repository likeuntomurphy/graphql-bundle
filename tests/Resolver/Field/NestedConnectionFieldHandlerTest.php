<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Resolver\Field\ConnectionResolver;
use Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Resolver\Field\NestedConnectionFieldHandler
 */
class NestedConnectionFieldHandlerTest extends TestCase
{
    public function testCallsFinderWithSourceAndParams(): void
    {
        $doc = new class implements GlobalObjectInterface {
            public function getId(): string
            {
                return 'id-1';
            }
        };

        $source = new \stdClass();
        $pageInfo = new PageInfo(false, 'id-1', 'id-1');

        $manager = $this->getMockBuilder(NestedConnectionManagerStub::class)
            ->onlyMethods(['findByParent'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('findByParent')
            ->with(
                self::identicalTo($source),
                self::callback(fn (CursorPaginationParams $p) => 5 === $p->first),
            )
            ->willReturn(new PaginatedResults([$doc], $pageInfo))
        ;

        $handler = new NestedConnectionFieldHandler($manager->findByParent(...), new ConnectionResolver());
        $info = $this->createStub(ResolveInfo::class);

        $result = $handler($source, ['first' => 5], [], $info);

        $this->assertInstanceOf(Connection::class, $result);
        $this->assertCount(1, $result->edges);
        $this->assertSame($doc, $result->edges[0]->node);
    }

    public function testPassesAfterArg(): void
    {
        $pageInfo = new PageInfo(false, null, null);

        $manager = $this->getMockBuilder(NestedConnectionManagerStub::class)
            ->onlyMethods(['findByParent'])
            ->getMock()
        ;

        $manager->expects(self::once())
            ->method('findByParent')
            ->with(
                self::anything(),
                self::callback(fn (CursorPaginationParams $p) => 'cursor-abc' === $p->after),
            )
            ->willReturn(new PaginatedResults([], $pageInfo))
        ;

        $handler = new NestedConnectionFieldHandler($manager->findByParent(...), new ConnectionResolver());
        $info = $this->createStub(ResolveInfo::class);

        $handler(new \stdClass(), ['after' => 'cursor-abc'], [], $info);
    }
}

/**
 * @internal
 */
class NestedConnectionManagerStub implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return \stdClass::class;
    }

    public function read(string $id): ?object
    {
        return null;
    }

    /** @return PaginatedResults<GlobalObjectInterface> */
    public function findByParent(object $source, CursorPaginationParams $params): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }
}
