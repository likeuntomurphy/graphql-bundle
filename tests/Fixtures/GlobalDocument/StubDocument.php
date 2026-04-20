<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

/** Fixture shape used by MutationFieldResolverTest; unions the shapes each resolver test asserts on. */
class StubDocument
{
    public string $name;
    public object $project;
    public string $color;
    public string $group;

    /** @var array<string, mixed> */
    public array $nested;
}
