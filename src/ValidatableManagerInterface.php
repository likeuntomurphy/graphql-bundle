<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface ValidatableManagerInterface
{
    /** @return class-string<\UnitEnum> */
    public static function getValidationGroupEnum(): string;
}
