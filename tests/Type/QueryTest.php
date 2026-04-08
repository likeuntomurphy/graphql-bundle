<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Type\Query;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\Query
 */
class QueryTest extends TestCase
{
    public function testAllFieldsFromIteratorAreRegistered(): void
    {
        $fieldA = new FieldDefinition(['name' => 'alpha', 'type' => Type::string()]);
        $fieldB = new FieldDefinition(['name' => 'beta', 'type' => Type::int()]);

        $query = new Query(new \ArrayObject([$fieldA, $fieldB]));

        $this->assertInstanceOf(FieldDefinition::class, $query->getField('alpha'));
        $this->assertInstanceOf(FieldDefinition::class, $query->getField('beta'));
    }

    public function testEmptyIteratorProducesNoFields(): void
    {
        $query = new Query(new \ArrayObject([]));

        $this->assertSame([], $query->getFields());
    }
}
