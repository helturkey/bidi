<?php

declare(strict_types=1);

namespace Hussein\Bidirection;

use Hussein\Bidirection\Support\Unicode;
use InvalidArgumentException;

/**
 * Width-based wrapping for logical text.
 *
 * Width is measured in extended grapheme clusters, not terminal columns or
 * rendered pixels. This class does not perform BiDi reordering.
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

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $paragraphs = explode("\n", $normalized);
        $wrapped = [];

        foreach ($paragraphs as $paragraph) {
            foreach (self::wrapParagraph($paragraph, $width, $cut) as $line) {
                $wrapped[] = $line;
            }
        }

        return implode($break, $wrapped);
    }

    /**
     * @return list<string>
     */
    private static function wrapParagraph(string $paragraph, int $width, bool $cut): array
    {
        $clusters = Unicode::graphemes($paragraph);
        if (count($clusters) <= $width) {
            return [$paragraph];
        }

        $lines = [];

        while (count($clusters) > $width) {
            $breakAt = null;

            for ($i = $width - 1; $i >= 1; --$i) {
                if (!self::isWhitespace($clusters[$i])) {
                    continue;
                }

                // Do not break a run of leading indentation as if it were a
                // word separator; if necessary, cut the line at its width.
                $hasContent = false;
                for ($prefix = 0; $prefix < $i; ++$prefix) {
                    if (!self::isWhitespace($clusters[$prefix])) {
                        $hasContent = true;
                        break;
                    }
                }

                if ($hasContent) {
                    $breakAt = $i;
                    break;
                }
            }

            if ($breakAt !== null) {
                $lines[] = implode('', array_slice($clusters, 0, $breakAt));
                $clusters = array_slice($clusters, $breakAt + 1);
                $clusters = self::dropLeadingWhitespace($clusters);
                continue;
            }

            if (!$cut) {
                $nextWhitespace = null;
                for ($i = $width; $i < count($clusters); ++$i) {
                    if (self::isWhitespace($clusters[$i])) {
                        $nextWhitespace = $i;
                        break;
                    }
                }

                if ($nextWhitespace !== null) {
                    $lines[] = implode('', array_slice($clusters, 0, $nextWhitespace));
                    $clusters = array_slice($clusters, $nextWhitespace + 1);
                    $clusters = self::dropLeadingWhitespace($clusters);
                    continue;
                }

                // Keep an unbreakable word intact when cut=false, even if it
                // makes this line longer than width.
                return [...$lines, implode('', $clusters)];
            }

            $lines[] = implode('', array_slice($clusters, 0, $width));
            $clusters = array_slice($clusters, $width);
        }

        $lines[] = implode('', $clusters);

        return $lines;
    }

    private static function isWhitespace(string $cluster): bool
    {
        return preg_match('/^\s+$/u', $cluster) === 1;
    }

    /**
     * @param list<string> $clusters
     * @return list<string>
     */
    private static function dropLeadingWhitespace(array $clusters): array
    {
        while ($clusters !== [] && self::isWhitespace($clusters[0])) {
            array_shift($clusters);
        }

        return $clusters;
    }
}
