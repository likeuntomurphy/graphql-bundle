<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Doctrine\Common\Collections\Collection;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;

class ProjectWithAttachments implements GlobalObjectInterface
{
    protected string $id;
    protected string $name;

    /** @var Collection<int, Attachment> */
    protected Collection $attachments;

    public function getId(): string
    {
        return $this->id;
    }
}
