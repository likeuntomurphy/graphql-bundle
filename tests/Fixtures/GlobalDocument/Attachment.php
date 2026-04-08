<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\GlobalObjectInterface;

class Attachment implements GlobalObjectInterface
{
    protected string $id;
    protected string $name;

    public function getId(): string
    {
        return $this->id;
    }
}
