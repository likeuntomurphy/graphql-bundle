<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Argument;

use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Argument\First;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Argument\First
 */
class FirstTest extends TestCase
{
    private First $argument;

    protected function setUp(): void
    {
        $this->argument = new First();
    }

    public function testNameIsFirst(): void
    {
        $this->assertSame('first', $this->argument->name);
    }

    public function testTypeIsInt(): void
    {
        $this->assertSame(Type::int(), $this->argument->getType());
    }

    public function testHasDescription(): void
    {
        $this->assertNotEmpty($this->argument->description);
    }
}
