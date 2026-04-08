<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\ValidationError
 */
class ValidationErrorTest extends TestCase
{
    public function testConstructorSetsPath(): void
    {
        $error = new ValidationError('email', 'is invalid');

        $this->assertSame('email', $error->path);
    }

    public function testConstructorSetsMessage(): void
    {
        $error = new ValidationError('email', 'is invalid');

        $this->assertSame('is invalid', $error->message);
    }
}
