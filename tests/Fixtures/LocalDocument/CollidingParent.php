<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument\Collision\Address;

class CollidingParent
{
    protected Address $address;
}
