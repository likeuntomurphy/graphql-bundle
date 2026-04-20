<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface UpdatableManagerInterface extends GlobalObjectManagerInterface
{
    /** @param list<\UnitEnum> $validationGroups */
    public function update(object $dto, object $document, array $validationGroups = []): object;
}
