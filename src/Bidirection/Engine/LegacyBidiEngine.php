<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine;

use Hussein\Bidirection\Contracts\BidiEngine;
use InvalidArgumentException;

/**
 * Adapter around the package's bundled BiDi implementation.
 *
 * Keeping the legacy algorithm behind this contract allows the implementation
 * to be replaced without changing the public facade.
 */
final class LegacyBidiEngine implements BidiEngine
{
    public function toVisual(string $text, bool|string $forceRtl = false): string
    {
        if (preg_match('//u', $text) !== 1) {
            throw new InvalidArgumentException('Text must be valid UTF-8.');
        }

        $bidi = new \Hussein\Bidirection\Engine\Legacy\BidiAlgorithm();
        $codePoints = $bidi->utf8Bidi(
            $bidi->UTF8StringToArray($text),
            $forceRtl
        );

        $result = '';
        foreach ($codePoints as $codePoint) {
            $result .= $bidi->unichr($codePoint);
        }

        return $result;
    }
}
