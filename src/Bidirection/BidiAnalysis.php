<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Com\Tecnick\Unicode\Convert;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Immutable analysis result in logical code-point order.
 *
 * Arrays are aligned by code point after X9 processing and optional shaping.
 */
final readonly class BidiAnalysis
{
    /**
     * @param list<int> $codePoints
     * @param list<int> $levels
     * @param list<int> $paragraphLevels
     */
    public function __construct(
        public array $codePoints,
        public array $levels,
        public array $paragraphLevels
    ) {
        $count = count($codePoints);
        if (count($levels) !== $count || count($paragraphLevels) !== $count) {
            throw new InvalidArgumentException(
                'Code points, embedding levels, and paragraph levels must have equal lengths.'
            );
        }
    }

    public function count(): int
    {
        return count($this->codePoints);
    }

    public function logicalText(): string
    {
        $convert = new Convert();

        return implode('', $convert->ordArrToChrArr($this->codePoints));
    }

    public function paragraphLevelAt(int $index): int
    {
        if (!array_key_exists($index, $this->paragraphLevels)) {
            throw new OutOfBoundsException('No paragraph level exists at this code point index.');
        }

        return $this->paragraphLevels[$index];
    }
}
