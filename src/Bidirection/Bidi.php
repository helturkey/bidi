<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Hussein\Bidirection\Engine\UnicodeBidiEngine;
use InvalidArgumentException;

/**
 * Public entry point for logical-to-visual conversion.
 *
 * Use this only for output targets that do not implement Unicode BiDi
 * themselves (for example, some legacy image or PDF drawing APIs).
 */
final class Bidi
{
    private function __construct()
    {
    }

    /**
     * Analyze logical text without visually reordering it.
     */
    public static function analyze(
        string $text,
        Direction|string $direction = Direction::Auto,
        bool $shapeArabic = true
    ): BidiAnalysis {
        return (new UnicodeBidiEngine())->analyze(
            $text,
            Direction::normalize($direction),
            $shapeArabic
        );
    }

    /**
     * Reorder one logical line using levels resolved for its complete paragraph.
     *
     * The caller must break the paragraph first and pass the corresponding
     * code-point and level slices. Paragraph level is 0 for LTR or 1 for RTL.
     *
     * @param list<int> $codePoints
     * @param list<int> $levels
     */
    public static function reorderLine(
        array $codePoints,
        array $levels,
        int $paragraphLevel
    ): string {
        return (new UnicodeBidiEngine())->reorderLine($codePoints, $levels, $paragraphLevel);
    }

    /**
     * Convert a string or a list of independent lines into visual order.
     *
     * Array items are processed as independent paragraphs and returned as a
     * numerically indexed list. For text laid out as one mixed-direction
     * paragraph, convert the complete paragraph rather than each display line.
     *
     * @param string|array<array-key, mixed> $text
     * @return string|list<string>
     */
    public static function toVisual(
        string|array $text,
        Direction|string $direction = Direction::Auto,
        bool $shapeArabic = true
    ): string|array {
        $baseDirection = Direction::normalize($direction);
        $engine = new UnicodeBidiEngine();

        if (is_string($text)) {
            return $text === ''
                ? ''
                : $engine->toVisual($text, $baseDirection, $shapeArabic);
        }

        $result = [];
        foreach ($text as $key => $line) {
            if (!is_string($line)) {
                throw new InvalidArgumentException(sprintf(
                    'Expected every array item to be a string; item %s is %s.',
                    (string) $key,
                    get_debug_type($line)
                ));
            }

            $result[] = $line === ''
                ? ''
                : $engine->toVisual($line, $baseDirection, $shapeArabic);
        }

        return $result;
    }
}
