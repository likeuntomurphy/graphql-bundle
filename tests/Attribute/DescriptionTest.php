<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Attribute;

use Likeuntomurphy\GraphQL\Attribute\Description;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Attribute\Description
 */
class DescriptionTest extends TestCase
{
    public function testStoresDescription(): void
    {
        $this->assertSame('A field description', (new Description('A field description'))->description);
    }

    public function testTargetsProperties(): void
    {
        $ref = new \ReflectionClass(Description::class);
        $attrs = $ref->getAttributes(\Attribute::class);

        $this->assertCount(1, $attrs);
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attrs[0]->newInstance()->flags);
    }
}
