<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class UnknownTypeException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct('Unknown type: '.$id);
    }
}
