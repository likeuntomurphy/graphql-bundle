<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use Likeuntomurphy\GraphQL\Type\TypeInterface;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\TypeInterface
 */
class TypeInterfaceTest extends TestCase
{
    public function testHasAutoconfigureTagForTypes(): void
    {
        $ref = new \ReflectionClass(TypeInterface::class);
        $attrs = $ref->getAttributes(AutoconfigureTag::class);

        $this->assertCount(1, $attrs);
        $this->assertSame(TypeRegistry::TAG, $attrs[0]->getArguments()[0]);
    }
}
