<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Repository\Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Iterator\IterableResult;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;
use Likeuntomurphy\GraphQL\Exception\InvalidCursorException;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Repository\Doctrine\ODM\MongoDB\CursorPaginatedRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Repository\Doctrine\ODM\MongoDB\CursorPaginatedRepository
 */
class CursorPaginatedRepositoryTest extends TestCase
{
    /** A valid 24-char hex ObjectId string used as the pagination cursor in every test. */
    private const AFTER = '507f191e810c19729de860ea';

    public function testReturnsDocumentsAndPageInfo(): void
    {
        $docA = new FakeDocument('507f191e810c19729de860eb');
        $docB = new FakeDocument('507f191e810c19729de860ec');

        $result = $this->buildRepository([$docA, $docB])->findWithPageInfo($this->params(5));

        $this->assertSame([$docA, $docB], $result->results);
        $this->assertInstanceOf(PageInfo::class, $result->pageInfo);
    }

    public function testHasNextPageAndTrimsToBatchSizeWhenExtraDocumentFetched(): void
    {
        $docs = [
            new FakeDocument('507f191e810c19729de860eb'),
            new FakeDocument('507f191e810c19729de860ec'),
            new FakeDocument('507f191e810c19729de860ed'), // extra sentinel
        ];

        $result = $this->buildRepository($docs)->findWithPageInfo($this->params(2));

        $this->assertTrue($result->pageInfo->hasNextPage);
        $this->assertCount(2, $result->results);
    }

    public function testHasNoNextPageWhenResultsDoNotExceedFirst(): void
    {
        $docs = [
            new FakeDocument('507f191e810c19729de860eb'),
            new FakeDocument('507f191e810c19729de860ec'),
        ];

        $result = $this->buildRepository($docs)->findWithPageInfo($this->params(5));

        $this->assertFalse($result->pageInfo->hasNextPage);
        $this->assertCount(2, $result->results);
    }

    public function testHasPreviousPageIsAlwaysFalse(): void
    {
        $result = $this->buildRepository([])->findWithPageInfo($this->params(5));

        $this->assertFalse($result->pageInfo->hasPreviousPage);
    }

    public function testStartAndEndCursorsAreFirstAndLastDocumentIds(): void
    {
        $docs = [
            new FakeDocument('507f191e810c19729de860eb'),
            new FakeDocument('507f191e810c19729de860ec'),
            new FakeDocument('507f191e810c19729de860ed'),
        ];

        $result = $this->buildRepository($docs)->findWithPageInfo($this->params(5));

        $this->assertSame('507f191e810c19729de860eb', $result->pageInfo->startCursor);
        $this->assertSame('507f191e810c19729de860ed', $result->pageInfo->endCursor);
    }

    public function testCursorsAreNullWhenNoDocumentsReturned(): void
    {
        $result = $this->buildRepository([])->findWithPageInfo($this->params(5));

        $this->assertNull($result->pageInfo->startCursor);
        $this->assertNull($result->pageInfo->endCursor);
    }

    public function testNullFilterIsIgnored(): void
    {
        $doc = new FakeDocument('507f191e810c19729de860eb');

        $result = $this->buildRepository([$doc])->findWithPageInfo($this->params(5), null);

        $this->assertSame([$doc], $result->results);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsInvalidCursorForMalformedCursor(): void
    {
        $repo = $this->createPartialMock(CursorPaginatedRepository::class, ['createQueryBuilder']);

        $this->expectException(InvalidCursorException::class);

        $params = (new CursorPaginationParams(5))
            ->setFirst(5)
            ->setAfter('not-a-valid-objectid')
        ;

        $repo->findWithPageInfo($params);
    }

    public function testFilterIsAppliedToQuery(): void
    {
        $doc = new FakeDocument('507f191e810c19729de860eb');
        $callCount = 0;
        $filter = function (Builder $qb) use (&$callCount): void {
            ++$callCount;
        };

        $this->buildRepository([$doc])->findWithPageInfo($this->params(5), $filter);

        $this->assertSame(1, $callCount);
    }

    private function params(int $first): CursorPaginationParams
    {
        return (new CursorPaginationParams($first))
            ->setFirst($first)
            ->setAfter(self::AFTER)
        ;
    }

    /**
     * @param list<mixed> $documents
     *
     * @return CursorPaginatedRepository<GlobalObjectInterface>
     */
    private function buildRepository(array $documents): CursorPaginatedRepository
    {
        $repo = $this->createPartialMock(CursorPaginatedRepository::class, ['createQueryBuilder']);

        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($this->buildQueryBuilder($documents))
        ;

        return $repo;
    }

    /**
     * @param list<mixed> $results
     */
    private function buildQueryBuilder(array $results): Builder
    {
        $iterator = $this->createStub(Iterator::class);
        $iterator->method('toArray')->willReturn($results);

        $query = $this->createStub(IterableResult::class);
        $query->method('getIterator')->willReturn($iterator);

        $qb = $this->createStub(Builder::class);
        $qb->method('sort')->willReturnSelf();
        $qb->method('field')->willReturnSelf();
        $qb->method('gt')->willReturnSelf();
        $qb->method('limit')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }
}

/** @internal */
class FakeDocument
{
    public function __construct(
        private string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
