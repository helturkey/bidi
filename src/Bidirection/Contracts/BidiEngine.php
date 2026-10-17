<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Contracts;

use Hussein\Bidirection\Direction;

/**
 * Converts logical Unicode text for renderers without native BiDi support.
 */
interface BidiEngine
{
    public function toVisual(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): string;
}
