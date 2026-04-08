<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Attribute;

use Likeuntomurphy\GraphQL\Attribute\Name;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Attribute\Name
 */
class NameTest extends TestCase
{
    public function testStoresName(): void
    {
        $this->assertSame('customName', (new Name('customName'))->name);
    }

    public function testTargetsClasses(): void
    {
        $ref = new \ReflectionClass(Name::class);
        $attrs = $ref->getAttributes(\Attribute::class);

        $this->assertCount(1, $attrs);
        $this->assertSame(\Attribute::TARGET_CLASS, $attrs[0]->newInstance()->flags);
    }
}
