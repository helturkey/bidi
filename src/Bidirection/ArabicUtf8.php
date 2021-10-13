<?php

namespace Hussein\Bidirection;

require_once 'Lib' . DIRECTORY_SEPARATOR . 'Bidi.class.php';

class ArabicUtf8
{
    /**
     * @param array|string $text
     * @param false|string $forcertl
     *
     *    L    Left-to-Right    LRM, most alphabetic, syllabic, Han ideographs, non-European or non-Arabic digits, ...
     *    R    Right-to-Left    RLM, Hebrew alphabet, and related punctuation
     *    AL   Right-to-Left Arabic    ALM, Arabic, Thaana, and Syriac alphabets, most punctuation specific to those scripts, ...
     *
     * @return array|string
     */
    public static function convert(array|string $text, bool|string $forcertl = false): array|string
    {
        $bidi = new \Bidi();
        if (\is_array($text)) {
            $str = array();
            foreach ($text as $line) {
                $chars = $bidi->utf8Bidi($bidi->UTF8StringToArray($line), $forcertl);
                $line = '';
                foreach ($chars as $char) {
                    $line .= $bidi->unichr($char);
                }

                $str[] = $line;
            }
            return $str;
        } elseif (\is_string($text)) {
            $chars = $bidi->utf8Bidi($bidi->UTF8StringToArray($text), $forcertl);
            $line = '';
            foreach ($chars as $char) {
                $line .= $bidi->unichr($char);
            }

            return $line;
        }
    }

    /**
     * @param string $string
     * @param int $width
     * @param string $break
     * @param bool $cut
     * @return string
     */

    public static function mb_wordwrap(string $string, int $width = 75, $break = "\n", bool $cut = false): string
    {
        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        $break = (string)$break;
        if ($break === '') {
            trigger_error('Break string cannot be empty', E_USER_ERROR);
        }

        $width = (int)$width;
        if ($width === 0 && $cut) {
            trigger_error('Cannot force cut when width is zero', E_USER_ERROR);
        }

        if (strlen($string) === mb_strlen($string)) {
            return wordwrap($string, $width, $break, $cut);
        }

        $stringWidth = mb_strlen($string);
        $breakWidth = mb_strlen($break);

        $result = '';
        $lastStart = $lastSpace = 0;

        for ($current = 0; $current < $stringWidth; $current++) {
            $char = mb_substr($string, $current, 1);

            $possibleBreak = $char;
            if ($breakWidth !== 1) {
                $possibleBreak = mb_substr($string, $current, $breakWidth);
            }

            if ($possibleBreak === $break) {
                $result .= mb_substr($string, $lastStart, $current - $lastStart + $breakWidth);
                $current += $breakWidth - 1;
                $lastStart = $lastSpace = $current + 1;
                continue;
            }

            if ($char === ' ') {
                if ($current - $lastStart >= $width) {
                    $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
                    $lastStart = $current + 1;
                }

                $lastSpace = $current;
                continue;
            }

            if ($current - $lastStart >= $width && $cut && $lastStart >= $lastSpace) {
                $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
                $lastStart = $lastSpace = $current;
                continue;
            }

            if ($current - $lastStart >= $width && $lastStart < $lastSpace) {
                $result .= mb_substr($string, $lastStart, $lastSpace - $lastStart) . $break;
                $lastStart = $lastSpace = $lastSpace + 1;
                continue;
            }
        }

        if ($lastStart !== $current) {
            $result .= mb_substr($string, $lastStart, $current - $lastStart);
        }

        return $result;
    }
}
