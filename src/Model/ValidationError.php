<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

readonly class ValidationError
{
    public function __construct(
        public string $path,
        public string $message,
    ) {
    }
}
