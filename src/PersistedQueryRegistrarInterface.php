<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface PersistedQueryRegistrarInterface
{
    /**
     * Persist a validated query string keyed by its ID (typically a hash).
     */
    public function save(string $id, string $query): void;
}
