<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Data;

/**
 * Cached access to the generated Unicode tables.
 */
final class UnicodeData
{
    /** @var array<string, array<int|string, mixed>>|null */
    private static ?array $cached = null;

    private function __construct()
    {
    }

    /**
     * @return array{
     *   bidiClass: array<int, string>,
     *   mirrored: array<int, int>,
     *   arabicForms: array<int, array<int, int>>,
     *   lamAlefForms: array<int, array<int, int>>,
     *   diacritics: array<int, mixed>,
     *   nonJoiningBefore: array<int, int>,
     *   alefLetters: array<int, int>,
     *   punctuation: array<int, int>
     * }
     */
    public static function tables(): array
    {
        return self::$cached ??= UnicodeTables::load();
    }
}
