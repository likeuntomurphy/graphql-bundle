<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface ValidationGroupsAwareInterface
{
    /**
     * @param 'create'|'update' $method
     *
     * @return list<string>
     */
    public function getValidationGroups(string $method, object $document): array;
}
