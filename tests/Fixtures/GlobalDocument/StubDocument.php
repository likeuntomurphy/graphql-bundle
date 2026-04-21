<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\Color;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\Tier;

/** Fixture shape used by MutationFieldResolverTest; unions the shapes each resolver test asserts on. */
class StubDocument
{
    public string $name;
    public object $project;
    public Color $color;
    public Tier $group;

    /** @var array<string, mixed> */
    public array $nested;
}
