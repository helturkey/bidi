<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Hussein\Bidirection\Contracts\BidiEngine;
use Hussein\Bidirection\Engine\LegacyBidiEngine;
use InvalidArgumentException;

/**
 * Public API for converting logical text into visual order.
 *
 * Only use visual conversion for rendering targets that do not implement
 * Unicode BiDi themselves (for example, some bitmap-image text APIs).
 */
final class Bidi
{
    private static ?BidiEngine $engine = null;

    private function __construct()
    {
    }

    public static function toVisual(string|array $text, bool|string $forceRtl = false): string|array
    {
        $engine = self::engine();

        if (is_string($text)) {
            return $engine->toVisual($text, $forceRtl);
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

            // Preserve the historical behavior of returning a numerically
            // indexed list for array input.
            $result[] = $engine->toVisual($line, $forceRtl);
        }

        return $result;
    }

    /**
     * Inject an engine, primarily for testing and application-specific engines.
     */
    public static function useEngine(?BidiEngine $engine): void
    {
        self::$engine = $engine;
    }

    private static function engine(): BidiEngine
    {
        if (self::$engine === null) {
            self::$engine = new LegacyBidiEngine();
        }

        return self::$engine;
    }
}
