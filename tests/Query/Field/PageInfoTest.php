<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Query\Field;

use Likeuntomurphy\GraphQL\Field\PageInfo;
use Likeuntomurphy\GraphQL\Type\PageInfo as PageInfoType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Field\PageInfo
 */
class PageInfoTest extends TestCase
{
    public function testNameIsPageInfo(): void
    {
        $pageInfoType = new PageInfoType();
        $field = new PageInfo($pageInfoType);

        $this->assertSame('pageInfo', $field->name);
    }

    public function testTypeIsPageInfoType(): void
    {
        $pageInfoType = new PageInfoType();
        $field = new PageInfo($pageInfoType);

        $this->assertSame($pageInfoType, $field->getType());
    }
}
