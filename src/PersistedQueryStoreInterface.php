<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface PersistedQueryStoreInterface
{
    /**
     * Return the query string for the given persisted query ID, or null if not found.
     */
    public function get(string $id): ?string;
}
