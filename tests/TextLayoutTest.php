<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\TextLayout;
use PHPUnit\Framework\TestCase;

final class TextLayoutTest extends TestCase
{
    public function testWrapsAtWhitespace(): void
    {
        self::assertSame("hello\nworld", TextLayout::wrap('hello world', 5));
    }

    public function testCutCanSplitLongWords(): void
    {
        self::assertSame("abcdef\ngh", TextLayout::wrap('abcdefgh', 6, "\n", true));
    }

    public function testDoesNotCutLongWordsWhenCutIsFalse(): void
    {
        self::assertSame('abcdefgh', TextLayout::wrap('abcdefgh', 6));
    }

    public function testPreservesExistingParagraphBreaks(): void
    {
        self::assertSame("one\ntwo", TextLayout::wrap("one\r\ntwo", 75));
    }

    public function testPreservesEmptyParagraphs(): void
    {
        self::assertSame("one\n\ntwo", TextLayout::wrap("one\n\ntwo", 75));
    }

    public function testUsesCustomSeparator(): void
    {
        self::assertSame('one | two', TextLayout::wrap('one two', 4, ' | '));
    }

    public function testRejectsInvalidWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextLayout::wrap('text', 0);
    }

    public function testRejectsEmptyBreak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextLayout::wrap('text', 2, '');
    }

    public function testRejectsInvalidUtf8(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextLayout::wrap("\xC3\x28", 2);
    }

    public function testWrapsArabicWithoutSplittingGraphemes(): void
    {
        self::assertSame("مرح\nبا", TextLayout::wrap('مرحبا', 3, "\n", true));
    }

    public function testDoesNotProduceAnEmptyLineForLeadingWhitespace(): void
    {
        self::assertSame("  ab\ncd", TextLayout::wrap("  abcd", 4, "\n", true));
    }
}
