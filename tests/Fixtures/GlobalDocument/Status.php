<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

enum Status: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
}
