<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class InvalidCursorException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("An invalid 'after' cursor was received from the client.");
    }
}
