<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\ArabicUtf8;
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
        self::assertSame("one\ntwo", ArabicUtf8::mb_wordwrap("one\ntwo", 75));
    }

    public function testRejectsInvalidWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextLayout::wrap('text', 0);
    }

    public function testWrapsArabicWithoutSplittingGraphemes(): void
    {
        self::assertSame("مرح\nبا", TextLayout::wrap('مرحبا', 3, "\n", true));
    }
}
