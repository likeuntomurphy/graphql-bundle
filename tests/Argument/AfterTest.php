<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Argument;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Argument\After;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Argument\After
 */
class AfterTest extends TestCase
{
    private After $argument;

    protected function setUp(): void
    {
        $this->argument = new After();
    }

    public function testNameIsAfter(): void
    {
        $this->assertSame('after', $this->argument->name);
    }

    public function testTypeIsId(): void
    {
        $this->assertSame(Type::id(), $this->argument->getType());
    }

    public function testHasDescription(): void
    {
        $this->assertNotEmpty($this->argument->description);
    }
}
