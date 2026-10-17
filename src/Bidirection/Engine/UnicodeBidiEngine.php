<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine;

use Com\Tecnick\Unicode\Bidi as UnicodeBidi;
use Hussein\Bidirection\Contracts\BidiEngine;
use Hussein\Bidirection\Direction;
use Hussein\Bidirection\Support\Unicode;

/**
 * Adapter over the maintained Unicode UBA implementation.
 *
 * This package intentionally does not duplicate the UBA algorithm or its
 * generated Unicode property tables.
 */
final class UnicodeBidiEngine implements BidiEngine
{
    public function toVisual(
        string $text,
        Direction $direction = Direction::Auto,
        bool $shapeArabic = true
    ): string {
        Unicode::assertValidUtf8($text);

        $bidi = new UnicodeBidi(
            $text,
            null,
            null,
            $direction->value,
            $shapeArabic
        );

        return $bidi->getString();
    }
}
