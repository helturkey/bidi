<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Hussein\Bidirection\Support\Unicode;
use InvalidArgumentException;
use OutOfBoundsException;

/** Immutable analysis result in logical code-point order. */
final readonly class BidiAnalysis
{
    /** @param list<int> $codePoints @param list<int> $levels @param list<int> $paragraphLevels */
    public function __construct(
        public array $codePoints,
        public array $levels,
        public array $paragraphLevels,
        public string $visualText = ''
    ) {
        $n=count($codePoints);
        if (count($levels)!==$n||count($paragraphLevels)!==$n) throw new InvalidArgumentException('Code points, levels, and paragraph levels must have equal lengths.');
    }
    public function count(): int { return count($this->codePoints); }
    public function logicalText(): string { return Unicode::fromCodePoints($this->codePoints); }
    public function paragraphLevelAt(int $index): int
    {
        if (!array_key_exists($index,$this->paragraphLevels)) throw new OutOfBoundsException('No paragraph level exists at this code point index.');
        return $this->paragraphLevels[$index];
    }
}
