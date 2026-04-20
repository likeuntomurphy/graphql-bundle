<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\FullWidgetManager;

#[GlobalObject(manager: FullWidgetManager::class)]
class Widget implements GlobalObjectInterface
{
    public function __construct(
        public string $id = '',
        public string $name = '',
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
