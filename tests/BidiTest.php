<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\Data\UnicodeData;
use Hussein\Bidirection\Direction;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BidiTest extends TestCase
{
    public function testAsciiTextIsUnchanged(): void
    {
        self::assertSame('hello world', Bidi::toVisual('hello world'));
        self::assertSame('price 123.45 USD', Bidi::toVisual('price 123.45 USD', Direction::Rtl, false));
    }

    public function testMixedLtrAndArabicTextIsReordered(): void
    {
        self::assertSame('abc با', Bidi::toVisual('abc اب', Direction::Ltr, false));
    }

    public function testRtlArabicParagraphKeepsWesternNumbersInNaturalOrder(): void
    {
        self::assertSame('123 رعسلا', Bidi::toVisual('السعر 123', Direction::Rtl, false));
    }

    public function testAutoDirectionUsesTheFirstStrongArabicCharacter(): void
    {
        self::assertSame('ABC رعسلا', Bidi::toVisual('السعر ABC', Direction::Auto, false));
    }

    public function testArabicShapingCanBeDisabledAndHasStableFormsWhenEnabled(): void
    {
        $plain = Bidi::toVisual('سلام', Direction::Rtl, false);
        $shaped = Bidi::toVisual('سلام', Direction::Rtl, true);

        self::assertSame('مالس', $plain);
        self::assertSame("\u{FEE1}\u{FE8E}\u{FEE0}\u{FEB3}", $shaped);
        self::assertNotSame($plain, $shaped);
    }

    public function testPairedBracketsAreBalancedInRtlArabicText(): void
    {
        self::assertSame('(12) با', Bidi::toVisual('اب (12)', Direction::Rtl, false));
    }

    public function testIsolateControlsArePreserved(): void
    {
        $text = 'abc ' . "\u{2067}" . 'اب' . "\u{2069}";
        $visual = Bidi::toVisual($text, Direction::Ltr, false);

        self::assertSame(1, substr_count($visual, "\u{2067}"));
        self::assertSame(1, substr_count($visual, "\u{2069}"));
        self::assertSame(1, preg_match('//u', $visual));
    }

    public function testExplicitEmbeddingControlsAreRemovedFromVisualOutput(): void
    {
        $text = 'a' . "\u{202B}" . 'ب' . "\u{202C}" . 'b';

        self::assertSame('aبb', Bidi::toVisual($text, Direction::Ltr, false));
    }

    public function testArrayInputProcessesEachEntryAsAnIndependentParagraph(): void
    {
        self::assertSame(
            ['رعسلا', 'English line', 'با'],
            Bidi::toVisual(['السعر', 'English line', 'اب'], Direction::Auto, false)
        );
    }

    public function testAnalysisReturnsLogicalCodePointsAndResolvedLevels(): void
    {
        $analysis = Bidi::analyze('abc اب', Direction::Ltr, false);

        self::assertSame('abc اب', $analysis->logicalText());
        self::assertSame([0, 0, 0, 0, 1, 1], $analysis->levels);
        self::assertSame([0, 0, 0, 0, 0, 0], $analysis->paragraphLevels);
        self::assertSame('abc با', $analysis->visualText);
        self::assertCount($analysis->count(), $analysis->levels);
        self::assertCount($analysis->count(), $analysis->paragraphLevels);
    }

    public function testAnalysisAssignsAnElevatedLevelToNumbersInRtlText(): void
    {
        $analysis = Bidi::analyze('السعر 123', Direction::Rtl, false);

        self::assertSame([1, 1, 1, 1, 1, 1, 2, 2, 2], $analysis->levels);
        self::assertSame([1, 1, 1, 1, 1, 1, 1, 1, 1], $analysis->paragraphLevels);
        self::assertSame('123 رعسلا', $analysis->visualText);
    }

    public function testParagraphSeparatorsArePreserved(): void
    {
        self::assertSame("hello\nعالم", Bidi::toVisual("hello\nعالم", Direction::Auto, false));
    }

    public function testReorderLineUsesResolvedLevelsForArabicCharacters(): void
    {
        self::assertSame('با', Bidi::reorderLine([0x0627, 0x0628], [1, 1], 1));
    }

    public function testReorderLinePreservesLtrRunsAndMirrorsBrackets(): void
    {
        self::assertSame(
            'abc (12)',
            Bidi::reorderLine(
                [0x0061, 0x0062, 0x0063, 0x0020, 0x0028, 0x0031, 0x0032, 0x0029],
                [0, 0, 0, 0, 1, 2, 2, 1],
                0
            )
        );

        self::assertSame(
            '(12)',
            Bidi::reorderLine([0x0028, 0x0031, 0x0032, 0x0029], [1, 2, 2, 1], 1)
        );
    }

    public function testReorderLineRejectsMismatchedArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::reorderLine([0x0627, 0x0628], [1], 1);
    }

    public function testReorderLineRejectsInvalidUnicodeScalars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::reorderLine([0xD800], [1], 1);
    }

    public function testReorderLineRejectsOutOfRangeLevels(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::reorderLine([0x0627], [126], 1);
    }

    public function testReorderLineRejectsInvalidParagraphLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::reorderLine([0x0627], [1], 2);
    }

    public function testInvalidUtf8SequencesAreRejected(): void
    {
        foreach (["\x80", "\xC0\xAF", "\xED\xA0\x80", "\xF4\x90\x80\x80", "\xE2\x82"] as $invalid) {
            try {
                Bidi::toVisual($invalid, Direction::Ltr, false);
                self::fail('Expected malformed UTF-8 to be rejected.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testValidSupplementaryScalarIsAccepted(): void
    {
        self::assertSame('A🙂', Bidi::toVisual('A🙂', Direction::Ltr, false));
    }

    public function testMaximumUnicodeScalarDoesNotCauseAnEncodingError(): void
    {
        self::assertIsString(Bidi::toVisual("\u{10FFFF}", Direction::Auto, false));
    }

    public function testInvalidDirectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::toVisual('text', 'sideways');
    }

    public function testNonStringArrayEntryIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bidi::toVisual(['valid', 123]);
    }

    public function testEmptyStringAndEmptyAnalysisAreWellDefined(): void
    {
        self::assertSame('', Bidi::toVisual(''));

        $analysis = Bidi::analyze('');
        self::assertSame([], $analysis->codePoints);
        self::assertSame([], $analysis->levels);
        self::assertSame([], $analysis->paragraphLevels);
        self::assertSame('', $analysis->logicalText());
        self::assertSame('', $analysis->visualText);
    }

    public function testMixedArabicInputsAlwaysProduceValidUtf8(): void
    {
        $samples = [
            'السعر ١٢٣٫٤٥ جنيهًا',
            'Price: السعر 125.50 USD',
            'ما بين (12) و34',
            'مقدمة ' . "\u{2067}" . 'ABC' . "\u{2069}" . ' نهاية',
            "العربية\nEnglish\n123",
            'سلام',
            'اب (12)',
        ];

        foreach ($samples as $sample) {
            $visual = Bidi::toVisual($sample, Direction::Auto, false);
            self::assertSame(1, preg_match('//u', $visual));
        }
    }
}
