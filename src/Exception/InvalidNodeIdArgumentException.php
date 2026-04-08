<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class InvalidNodeIdArgumentException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("An invalid 'id' argument was received from the client.");
    }
}
