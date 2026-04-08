<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Project
{
    protected string $id;

    #[GraphQL\Description('This is an int field')]
    protected int $int;

    #[GraphQL\Description('This is a float field')]
    protected float $float;

    #[GraphQL\Description('This is a string field')]
    protected string $string;

    #[GraphQL\Description('This is a boolean field')]
    protected bool $boolean;

    #[GraphQL\Description('This is a nullable int field')]
    protected ?int $nullableInt;

    #[GraphQL\Description('This is a nullable float field')]
    protected ?float $nullableFloat;

    #[GraphQL\Description('This is a nullable string field')]
    protected ?string $nullableString;

    #[GraphQL\Description('This is a nullable boolean field')]
    protected ?bool $nullableBoolean;

    #[GraphQL\Exclude]
    protected bool $exclude;
}
