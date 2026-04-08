<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Attribute;

use Likeuntomurphy\GraphQL\Attribute\Exclude;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Attribute\Exclude
 */
class ExcludeTest extends TestCase
{
    public function testTargetsProperties(): void
    {
        $ref = new \ReflectionClass(Exclude::class);
        $attrs = $ref->getAttributes(\Attribute::class);

        $this->assertCount(1, $attrs);
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attrs[0]->newInstance()->flags);
    }
}
