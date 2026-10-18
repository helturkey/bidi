<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Contracts;

use Hussein\Bidirection\BidiAnalysis;
use Hussein\Bidirection\Direction;

interface BidiEngine
{
    public function toVisual(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): string;

    public function analyze(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): BidiAnalysis;

    /**
     * @param array<array-key, mixed> $codePoints
     * @param array<array-key, mixed> $levels
     */
    public function reorderLine(array $codePoints, array $levels, int $paragraphLevel): string;
}
