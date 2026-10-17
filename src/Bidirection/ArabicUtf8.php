<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

/**
 * Backward-compatible entry point retained for existing package users.
 */
class ArabicUtf8
{
    /**
     * Convert logical text to visual order for renderers without BiDi support.
     *
     * @param array<string>|string $text
     * @param bool|string $forcertl Legacy direction override
     * @return array<string>|string
     */
    public static function convert(string|array $text, bool|string $forcertl = false): string|array
    {
        return Bidi::toVisual($text, $forcertl);
    }

    /**
     * Wrap text using grapheme-aware Unicode segmentation.
     */
    public static function mb_wordwrap(
        string $string,
        int $width = 75,
        string $break = "\n",
        bool $cut = false
    ): string {
        return TextLayout::wrap($string, $width, $break, $cut);
    }
}
