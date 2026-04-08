<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Pagination;

use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams
 */
class CursorPaginationParamsTest extends TestCase
{
    public function testFirstDefaultsToLimit(): void
    {
        $params = new CursorPaginationParams();

        $this->assertSame(CursorPaginationParams::LIMIT, $params->getFirst());
    }

    public function testFirstClampsToLimit(): void
    {
        $params = new CursorPaginationParams();
        $params->setFirst(CursorPaginationParams::LIMIT + 50);

        $this->assertSame(CursorPaginationParams::LIMIT, $params->getFirst());
    }

    public function testFirstAcceptsValueBelowLimit(): void
    {
        $params = new CursorPaginationParams();
        $params->setFirst(10);

        $this->assertSame(10, $params->getFirst());
    }

    public function testFirstTreatsNullAsDefault(): void
    {
        $params = new CursorPaginationParams();
        $params->setFirst(null);

        $this->assertSame(CursorPaginationParams::LIMIT, $params->getFirst());
    }

    public function testAfterDefaultsTo24ZeroCharacters(): void
    {
        $params = new CursorPaginationParams();

        $this->assertSame(str_repeat('0', 24), $params->getAfter());
    }

    public function testAfterAcceptsValue(): void
    {
        $params = new CursorPaginationParams();
        $params->setAfter('6579e4a1b3d2c1a0f8e7d6c5');

        $this->assertSame('6579e4a1b3d2c1a0f8e7d6c5', $params->getAfter());
    }

    public function testAfterTreatsNullAsDefault(): void
    {
        $params = new CursorPaginationParams();
        $params->setAfter(null);

        $this->assertSame(CursorPaginationParams::MIN_ID, $params->getAfter());
    }

    public function testCustomLimitCapsFirst(): void
    {
        $params = new CursorPaginationParams(25);
        $params->setFirst(50);

        $this->assertSame(25, $params->getFirst());
    }

    public function testCustomLimitDefaultsFirst(): void
    {
        $params = new CursorPaginationParams(25);

        $this->assertSame(25, $params->getFirst());
    }

    public function testCustomLimitAllowsValueBelow(): void
    {
        $params = new CursorPaginationParams(25);
        $params->setFirst(10);

        $this->assertSame(10, $params->getFirst());
    }
}
