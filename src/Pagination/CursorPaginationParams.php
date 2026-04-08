<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Pagination;

class CursorPaginationParams
{
    public const int LIMIT = 100;
    public const string MIN_ID = '000000000000000000000000';

    protected ?int $first = null;
    protected ?string $after = null;

    public function __construct(
        private int $limit = self::LIMIT,
    ) {
    }

    public function getFirst(): int
    {
        return min($this->first ?? $this->limit, $this->limit);
    }

    public function setFirst(?int $first): static
    {
        $this->first = $first;

        return $this;
    }

    public function getAfter(): string
    {
        return $this->after ?? self::MIN_ID;
    }

    public function setAfter(?string $after): static
    {
        $this->after = $after;

        return $this;
    }
}
