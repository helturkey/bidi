<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\Direction;
use PHPUnit\Framework\TestCase;

final class BidiTest extends TestCase
{
    public function testAsciiTextIsUnchanged(): void
    {
        self::assertSame('hello world', Bidi::toVisual('hello world'));
    }

    public function testMixedLtrAndHebrewTextIsReordered(): void
    {
        self::assertSame('abc גבא', Bidi::toVisual('abc אבג', Direction::Ltr, false));
    }

    public function testArabicShapingCanBeDisabled(): void
    {
        $plain = Bidi::toVisual('سلام', Direction::Rtl, false);
        $shaped = Bidi::toVisual('سلام', Direction::Rtl, true);

        self::assertNotSame($plain, $shaped);
        self::assertNotSame('', $shaped);
    }

    public function testIsolatesAreAccepted(): void
    {
        $result = Bidi::toVisual('abc ' . "\u{2067}" . 'אבג' . "\u{2069}", Direction::Ltr, false);

        self::assertIsString($result);
        self::assertNotSame('', $result);
    }

    public function testBracketsAndNumbersDoNotBreakMixedText(): void
    {
        $result = Bidi::toVisual('abc (123) אבג', Direction::Ltr, false);

        self::assertStringContainsString('123', $result);
        self::assertSame(1, preg_match('//u', $result));
    }

    public function testArrayInputReturnsListAndConvertsEachLine(): void
    {
        $result = Bidi::toVisual(['hello', 'אבג'], Direction::Auto, false);

        self::assertSame(['hello', 'גבא'], $result);
    }

    public function testInvalidUtf8IsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Bidi::toVisual("\xC3\x28");
    }

    public function testMaximumUnicodeScalarIsAccepted(): void
    {
        self::assertSame("\u{10FFFF}", Bidi::toVisual("\u{10FFFF}", Direction::Auto, false));
    }

    public function testInvalidDirectionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Bidi::toVisual('text', 'sideways');
    }

    public function testNonStringArrayEntryIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Bidi::toVisual(['valid', 123]);
    }
}
