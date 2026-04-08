<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

class Event
{
    protected string $id;

    protected \DateTimeImmutable $startsAt;

    protected ?\DateTimeImmutable $endsAt;
}
