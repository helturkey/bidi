<?php

namespace Hussein\BiDi;

require 'lib' . DIRECTORY_SEPARATOR . 'bidi.php';


class ArabicUtf8
{

    static public function arab_log2vis(array $text)
    {
        $bidi = new bidi();

        $str = array();

        foreach ($text as $line) {
            $chars = $bidi->utf8Bidi($bidi->UTF8StringToArray($line), 'AL');
            $line = '';
            foreach ($chars as $char) {
                $line .= $bidi->unichr($char);
            }

            $str[] = $line;
        }

        return implode("\n", $str);
    }
}
