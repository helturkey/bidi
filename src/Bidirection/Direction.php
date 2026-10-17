<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use InvalidArgumentException;

/**
 * Base direction used when resolving a paragraph.
 */
enum Direction: string
{
    case Auto = '';
    case Ltr = 'L';
    case Rtl = 'R';

    /**
     * Normalize the public direction input. Unknown values are rejected
     * rather than silently changing the caller's requested direction.
     */
    public static function normalize(self|string $direction): self
    {
        if ($direction instanceof self) {
            return $direction;
        }

        return match (strtoupper(trim($direction))) {
            '', 'AUTO' => self::Auto,
            'L', 'LTR', 'LEFT-TO-RIGHT' => self::Ltr,
            'R', 'RTL', 'RIGHT-TO-LEFT' => self::Rtl,
            default => throw new InvalidArgumentException(
                'Direction must be auto, L/LTR, or R/RTL.'
            ),
        };
    }
}
