<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface CreatableManagerInterface
{
    /** @param list<\UnitEnum> $validationGroups */
    public function create(object $dto, object $document, array $validationGroups = []): object;
}
