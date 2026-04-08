<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\GlobalObjectInterface;

class Widget implements GlobalObjectInterface
{
    public function __construct(
        protected string $id,
        public string $name,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
