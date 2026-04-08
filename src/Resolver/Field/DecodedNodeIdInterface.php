<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

interface DecodedNodeIdInterface
{
    public function getTypeName(): string;

    public function getId(): string;
}
