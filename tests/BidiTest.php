<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\Data\UnicodeData;
use PHPUnit\Framework\TestCase;

final class BidiTest extends TestCase
{
    protected function tearDown(): void
    {
        Bidi::useEngine(null);
    }

    public function testAsciiTextCanBeConverted(): void
    {
        self::assertSame('hello world', Bidi::toVisual('hello world'));
    }

    public function testArrayInputReturnsArray(): void
    {
        $result = Bidi::toVisual(['hello', 'world']);

        self::assertSame(['hello', 'world'], $result);
    }

    public function testInvalidUtf8IsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Bidi::toVisual("\xC3\x28");
    }

    public function testUnicodeTablesAreLoadedThroughNamespacedProvider(): void
    {
        $tables = UnicodeData::tables();

        self::assertArrayHasKey('bidiClass', $tables);
        self::assertArrayHasKey('mirrored', $tables);
        self::assertArrayHasKey('arabicForms', $tables);
        self::assertArrayNotHasKey('unicode', $GLOBALS);
        self::assertFalse(defined('K_RLE'));
    }
}
