<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface UpdatableManagerInterface
{
    /** @param list<\UnitEnum> $validationGroups */
    public function update(object $dto, object $document, array $validationGroups = []): object;
}
