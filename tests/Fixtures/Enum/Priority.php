<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Enum;

enum Priority: string
{
    case Low = 'low';
    case High = 'high';
}
