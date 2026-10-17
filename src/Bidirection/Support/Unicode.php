<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Support;

use InvalidArgumentException;

final class Unicode
{
    private function __construct()
    {
    }

    public static function assertValidUtf8(string $text): void
    {
        if (preg_match('//u', $text) !== 1) {
            throw new InvalidArgumentException('Text must be valid UTF-8.');
        }
    }

    /**
     * Split a UTF-8 string into grapheme clusters when PCRE supports \\X.
     *
     * @return list<string>
     */
    public static function graphemes(string $text): array
    {
        self::assertValidUtf8($text);

        if ($text === '') {
            return [];
        }

        $matched = preg_match_all('/\\X/u', $text, $matches);
        if ($matched === false) {
            throw new InvalidArgumentException('Unable to segment the UTF-8 string.');
        }

        return $matches[0];
    }
}
