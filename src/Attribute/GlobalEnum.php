<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class GlobalEnum
{
    public const string RESOURCE_TAG = 'graphql.enum';
}
