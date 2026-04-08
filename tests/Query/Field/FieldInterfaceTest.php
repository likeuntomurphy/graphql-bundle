<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Query\Field;

use Likeuntomurphy\GraphQL\Query\Field\FieldInterface;
use Likeuntomurphy\GraphQL\Type\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Query\Field\FieldInterface
 */
class FieldInterfaceTest extends TestCase
{
    public function testHasAutoconfigureTagForQueryFields(): void
    {
        $ref = new \ReflectionClass(FieldInterface::class);
        $attrs = $ref->getAttributes(AutoconfigureTag::class);

        $this->assertCount(1, $attrs);
        $this->assertSame(Query::FIELD_TAG, $attrs[0]->getArguments()[0]);
    }
}
