<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Tests;

use Hussein\Bidirection\Data\UnicodeData;
use PHPUnit\Framework\TestCase;

final class UnicodeDataTest extends TestCase
{
    public function testUnicodeDataVersionIsPinned(): void
    {
        self::assertSame('18.0.0', UnicodeData::VERSION);
    }

    public function testPropertyRangesAreSortedNonOverlappingAndWithinUnicodeRange(): void
    {
        foreach ([
            UnicodeData::BIDI_RANGES,
            UnicodeData::JOINING_RANGES,
        ] as $ranges) {
            $previousEnd = -1;

            foreach ($ranges as [$start, $end, $property]) {
                self::assertGreaterThanOrEqual(0, $start);
                self::assertLessThanOrEqual(0x10FFFF, $end);
                self::assertLessThanOrEqual($start, $end);
                self::assertGreaterThan($previousEnd, $start);
                self::assertNotSame('', $property);
                $previousEnd = $end;
            }
        }
    }

    public function testGeneratedTablesContainExpectedLatinAndArabicProperties(): void
    {
        self::assertSame('L', self::propertyAt(UnicodeData::BIDI_RANGES, 0x0041, 'L'));
        self::assertSame('AL', self::propertyAt(UnicodeData::BIDI_RANGES, 0x0627, 'L'));
        self::assertSame('R', self::propertyAt(UnicodeData::JOINING_RANGES, 0x0627, 'U'));
        self::assertSame('D', self::propertyAt(UnicodeData::JOINING_RANGES, 0x0628, 'U'));
    }

    public function testMirroringAndBracketTablesContainValidScalars(): void
    {
        foreach (UnicodeData::MIRRORING as $source => $target) {
            self::assertScalar($source);
            self::assertScalar($target);
        }

        foreach (UnicodeData::BRACKETS as $source => [$target, $kind]) {
            self::assertScalar($source);
            self::assertScalar($target);
            self::assertContains($kind, ['o', 'c']);
        }
    }

    /**
     * @param list<array{int, int, string}> $ranges
     */
    private static function propertyAt(array $ranges, int $codePoint, string $default): string
    {
        $low = 0;
        $high = count($ranges) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            [$start, $end, $property] = $ranges[$middle];

            if ($codePoint < $start) {
                $high = $middle - 1;
            } elseif ($codePoint > $end) {
                $low = $middle + 1;
            } else {
                return $property;
            }
        }

        return $default;
    }

    private static function assertScalar(int $codePoint): void
    {
        self::assertGreaterThanOrEqual(0, $codePoint);
        self::assertLessThanOrEqual(0x10FFFF, $codePoint);
        self::assertFalse($codePoint >= 0xD800 && $codePoint <= 0xDFFF);
    }
}
