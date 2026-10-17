<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine;

use Com\Tecnick\Unicode\Bidi as UnicodeBidi;
use Com\Tecnick\Unicode\Convert;
use Hussein\Bidirection\BidiAnalysis;
use Hussein\Bidirection\Contracts\BidiEngine;
use Hussein\Bidirection\Direction;
use Hussein\Bidirection\Support\Unicode;
use InvalidArgumentException;

/**
 * Adapter over the maintained Unicode UBA implementation.
 */
final class UnicodeBidiEngine implements BidiEngine
{
    public function toVisual(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): string {
        if ($text === '') {
            return '';
        }

        return $this->createProcessor($text, $direction, $shapeArabic)->getString();
    }

    public function analyze(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): BidiAnalysis {
        if ($text === '') {
            return new BidiAnalysis([], [], []);
        }

        $processor = $this->createProcessor($text, $direction, $shapeArabic);

        return new BidiAnalysis(
            $processor->getLogicalOrdArray(),
            $processor->getLogicalLevels(),
            $processor->getLogicalParagraphLevels()
        );
    }

    /**
     * @param array<array-key, mixed> $codePoints
     * @param array<array-key, mixed> $levels
     */
    public function reorderLine(array $codePoints, array $levels, int $paragraphLevel): string
    {
        if (count($codePoints) !== count($levels)) {
            throw new InvalidArgumentException('Code points and levels must have the same length.');
        }

        if ($paragraphLevel < 0 || $paragraphLevel > 125) {
            throw new InvalidArgumentException('Paragraph level must be between 0 and 125.');
        }

        $validatedCodePoints = [];
        foreach ($codePoints as $codePoint) {
            if (!is_int($codePoint) || $codePoint < 0 || $codePoint > 0x10FFFF
                || ($codePoint >= 0xD800 && $codePoint <= 0xDFFF)) {
                throw new InvalidArgumentException('Every code point must be a Unicode scalar value.');
            }

            $validatedCodePoints[] = $codePoint;
        }

        $validatedLevels = [];
        foreach ($levels as $level) {
            if (!is_int($level) || $level < 0 || $level > 125) {
                throw new InvalidArgumentException('Every embedding level must be between 0 and 125.');
            }

            $validatedLevels[] = $level;
        }

        $visual = UnicodeBidi::reorderLine($validatedCodePoints, $validatedLevels, $paragraphLevel);
        $convert = new Convert();

        return implode('', $convert->ordArrToChrArr($visual));
    }

    private function createProcessor(
        string $text,
        Direction $direction,
        bool $shapeArabic
    ): UnicodeBidi {
        Unicode::assertValidUtf8($text);

        return new UnicodeBidi($text, null, null, $direction->value, $shapeArabic);
    }
}
