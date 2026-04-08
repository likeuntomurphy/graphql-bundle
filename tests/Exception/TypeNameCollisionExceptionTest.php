<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Exception;

use Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Exception\TypeNameCollisionException
 */
class TypeNameCollisionExceptionTest extends TestCase
{
    public function testMessageContainsTypeNameAndBothClasses(): void
    {
        $exception = new TypeNameCollisionException('Project', 'App\Entity\Project', 'App\Admin\Project');

        $this->assertStringContainsString('Project', $exception->getMessage());
        $this->assertStringContainsString('App\Entity\Project', $exception->getMessage());
        $this->assertStringContainsString('App\Admin\Project', $exception->getMessage());
        $this->assertStringContainsString('#[Likeuntomurphy\GraphQL\Attribute\Name]', $exception->getMessage());
    }

    public function testExtendsLogicException(): void
    {
        $this->assertInstanceOf(\LogicException::class, new TypeNameCollisionException('Foo', 'A\Foo', 'B\Foo'));
    }
}
