<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Attribute\Type;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;

class WithTypeOverride implements GlobalObjectInterface
{
    public string $id;

    #[Type('Email')]
    public string $email;

    public string $plainString;

    public function getId(): string
    {
        return $this->id;
    }
}
