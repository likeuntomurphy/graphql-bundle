<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Tests\Fixtures\LocalObject\Address;

class Order
{
    public Address $address;
}
