<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

class GraphQLRequest
{
    public ?string $id = null;

    #[Assert\When(expression: 'this.id === null', constraints: [new Assert\NotBlank()])]
    public ?string $query = null;

    public ?string $operationName = null;

    /** @var array<string, mixed> */
    public array $variables = [];
}
