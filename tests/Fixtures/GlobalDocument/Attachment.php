<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\IdFieldManager;

#[GlobalObject(manager: IdFieldManager::class)]
class Attachment implements GlobalObjectInterface
{
    public Project $project;

    public string $url;
    protected string $id;

    public function getId(): string
    {
        return $this->id;
    }
}
