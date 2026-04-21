<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Repository\Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Likeuntomurphy\GraphQL\Exception\InvalidCursorException;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException;

/**
 * @template T of GlobalObjectInterface
 *
 * @extends DocumentRepository<T>
 */
class CursorPaginatedRepository extends DocumentRepository
{
    public const int DEFAULT_LIMIT = 100;

    /**
     * @param (?callable(Builder): mixed) $filter
     *
     * @return PaginatedResults<T>
     */
    public function findWithPageInfo(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults
    {
        $first = $params->first ?? self::DEFAULT_LIMIT;

        $qb = $this->createQueryBuilder()
            ->sort('id')
            ->limit($first + 1)
        ;

        if (null !== $params->after) {
            try {
                $afterId = new ObjectId($params->after);
            } catch (InvalidArgumentException) {
                throw new InvalidCursorException();
            }

            $qb->field('id')->gt($afterId);
        }

        if ($filter) {
            $filter($qb);
        }

        /** @var list<T> $results */
        $results = array_values($qb->getQuery()->getIterator()->toArray());

        $hasNextPage = count($results) > $first;

        if ($hasNextPage) {
            array_pop($results);
        }

        return new PaginatedResults(
            results: $results,
            pageInfo: new PageInfo(
                hasNextPage: $hasNextPage,
                startCursor: array_first($results)?->getId(),
                endCursor: array_last($results)?->getId(),
            ),
        );
    }
}
