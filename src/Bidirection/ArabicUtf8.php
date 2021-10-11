<?php

namespace Hussein\Bidirection;

require_once 'Lib' . DIRECTORY_SEPARATOR . 'Bidi.class.php';


class ArabicUtf8
{

    static public function convert(array|string $text, $forcertl = false)
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
}
