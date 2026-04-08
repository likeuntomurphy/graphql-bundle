<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Dto;

use Likeuntomurphy\GraphQL\Attribute\IdField;

class AttachmentDto
{
    #[IdField]
    public string $projectId;

    public string $url;
}
