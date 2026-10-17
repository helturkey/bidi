<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Hussein\Bidirection\Support\Unicode;
use InvalidArgumentException;

/**
 * Unicode-aware wrapping for text rendered by character-counting APIs.
 *
 * Width is measured in grapheme clusters, not terminal columns or font pixels.
 */
final class TextLayout
{
    private function __construct()
    {
    }

    public static function wrap(
        string $text,
        int $width = 75,
        string $break = "\n",
        bool $cut = false
    ): string {
        Unicode::assertValidUtf8($text);

        if ($width < 1) {
            throw new InvalidArgumentException('Width must be greater than zero.');
        }

        if ($break === '') {
            throw new InvalidArgumentException('Break string cannot be empty.');
        }

        if ($text === '') {
            return '';
        }

        $paragraphs = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        $wrapped = [];

        foreach ($paragraphs as $paragraph) {
            $wrapped = array_merge($wrapped, self::wrapParagraph($paragraph, $width, $cut));
        }

        return implode($break, $wrapped);
    }

    /** @return list<string> */
    private static function wrapParagraph(string $paragraph, int $width, bool $cut): array
    {
        $clusters = Unicode::graphemes($paragraph);
        if (count($clusters) <= $width) {
            return [$paragraph];
        }

        $lines = [];
        while (count($clusters) > $width) {
            $window = array_slice($clusters, 0, $width);
            $breakAt = null;

            for ($i = count($window) - 1; $i >= 0; --$i) {
                if (preg_match('/^\s$/u', $window[$i]) === 1) {
                    $breakAt = $i;
                    break;
                }
            }

            if ($breakAt !== null && $breakAt > 0) {
                $lines[] = implode('', array_slice($clusters, 0, $breakAt));
                $clusters = array_slice($clusters, $breakAt + 1);

                while ($clusters !== [] && preg_match('/^\s$/u', $clusters[0]) === 1) {
                    array_shift($clusters);
                }

                continue;
            }

            if (!$cut) {
                $nextSpace = null;
                for ($i = $width; $i < count($clusters); ++$i) {
                    if (preg_match('/^\s$/u', $clusters[$i]) === 1) {
                        $nextSpace = $i;
                        break;
                    }
                }

                if ($nextSpace !== null) {
                    $lines[] = implode('', array_slice($clusters, 0, $nextSpace));
                    $clusters = array_slice($clusters, $nextSpace + 1);

                    while ($clusters !== [] && preg_match('/^\s$/u', $clusters[0]) === 1) {
                        array_shift($clusters);
                    }

                    continue;
                }

                return array_merge($lines, [implode('', $clusters)]);
            }

            $lines[] = implode('', array_slice($clusters, 0, $width));
            $clusters = array_slice($clusters, $width);
        }

        $lines[] = implode('', $clusters);

        return $lines;
    }
}
