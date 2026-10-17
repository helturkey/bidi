<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine\Legacy;

use Hussein\Bidirection\Data\BidiControl;
use Hussein\Bidirection\Data\UnicodeData;

class BidiAlgorithm
{
    /**
     * @param $c
     * @return string
     */
    public function unichr($c)
    {
        if ($c <= 0x7F) {
            // one byte
            return chr($c);
        } else if ($c <= 0x7FF) {
            // two bytes
            return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0xFFFF) {
            // three bytes
            return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0x10FFFF) {
            // four bytes
            return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
        } else {
            return "";
        }
    }

    /**
     * Converts UTF-8 strings to codepoints array.
     * Invalid byte sequences will be replaced with 0xFFFD (replacement character)
     * Based on: http://www.faqs.org/rfcs/rfc3629.html
     *
     *      Char. number range  |        UTF-8 octet sequence
     *       (hexadecimal)    |              (binary)
     *    --------------------+-----------------------------------------------
     *    0000 0000-0000 007F | 0xxxxxxx
     *    0000 0080-0000 07FF | 110xxxxx 10xxxxxx
     *    0000 0800-0000 FFFF | 1110xxxx 10xxxxxx 10xxxxxx
     *    0001 0000-0010 FFFF | 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
     *    ---------------------------------------------------------------------
     *
     *   ABFN notation:
     *   ---------------------------------------------------------------------
     *   UTF8-octets = *( UTF8-char )
     *   UTF8-char   = UTF8-1 / UTF8-2 / UTF8-3 / UTF8-4
     *   UTF8-1      = %x00-7F
     *   UTF8-2      = %xC2-DF UTF8-tail
     *
     *   UTF8-3      = %xE0 %xA0-BF UTF8-tail / %xE1-EC 2( UTF8-tail ) /
     *                 %xED %x80-9F UTF8-tail / %xEE-EF 2( UTF8-tail )
     *   UTF8-4      = %xF0 %x90-BF 2( UTF8-tail ) / %xF1-F3 3( UTF8-tail ) /
     *                 %xF4 %x80-8F 2( UTF8-tail )
     *   UTF8-tail   = %x80-BF
     *   ---------------------------------------------------------------------
     *
     * @param string $str
     * @return array
     */
    public function UTF8StringToArray(string $str): array
    {
        $unicode = array(); // array containing unicode values
        $bytes = array(); // array containing single character byte sequences
        $numbytes = 1; // number of octetc needed to represent the UTF-8 character

        $str .= ""; // force $str to be a string
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            $char = ord($str[$i]); // get one string character at time
            if (count($bytes) == 0) { // get starting octect
                if ($char <= 0x7F) {
                    $unicode[] = $char; // use the character "as is" because is ASCII
                    $numbytes = 1;
                } elseif (($char >> 0x05) == 0x06) { // 2 bytes character (0x06 = 110 BIN)
                    $bytes[] = ($char - 0xC0) << 0x06;
                    $numbytes = 2;
                } elseif (($char >> 0x04) == 0x0E) { // 3 bytes character (0x0E = 1110 BIN)
                    $bytes[] = ($char - 0xE0) << 0x0C;
                    $numbytes = 3;
                } elseif (($char >> 0x03) == 0x1E) { // 4 bytes character (0x1E = 11110 BIN)
                    $bytes[] = ($char - 0xF0) << 0x12;
                    $numbytes = 4;
                } else {
                    // use replacement character for other invalid sequences
                    $unicode[] = 0xFFFD;
                    $bytes = array();
                    $numbytes = 1;
                }
            } elseif (($char >> 0x06) == 0x02) { // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
                $bytes[] = $char - 0x80;
                if (count($bytes) == $numbytes) {
                    // compose UTF-8 bytes to a single unicode value
                    $char = $bytes[0];
                    for ($j = 1; $j < $numbytes; $j++) {
                        $char += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
                    }
                    if ((($char >= 0xD800) and ($char <= 0xDFFF)) or ($char >= 0x10FFFF)) {
                        /* The definition of UTF-8 prohibits encoding character numbers between
                        U+D800 and U+DFFF, which are reserved for use with the UTF-16
                        encoding form (as surrogate pairs) and do not directly represent
                        characters. */
                        $unicode[] = 0xFFFD; // use replacement character
                    } else {
                        $unicode[] = $char; // add char to array
                    }
                    // reset data for next char
                    $bytes = array();
                    $numbytes = 1;
                }
            } else {
                // use replacement character for other invalid sequences
                $unicode[] = 0xFFFD;
                $bytes = array();
                $numbytes = 1;
            }
        }
        return $unicode;
    }

    /**
     * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param array $ta array of characters composing the string.
     * @param false|string $forcertl 'R' forces RTL, 'L' forces LTR, or 'AL' for dynamic
     * @return array
     */
    public function utf8Bidi(array $ta, bool|string $forcertl = false): array
    {
        // before we go, replace 160 CS NO-BREAK SPACE with 32 space to avoid لا from ل 160 ا
        // php > 7.4
        // $ta = array_map(fn(int $value) => $value === 160 ? 32 : $value, $ta);
        // solved by passing CS to Arabic

        $tables = UnicodeData::tables();
        $unicode = $tables['bidiClass'];
        $unicode_mirror = $tables['mirrored'];
        $unicode_arlet = $tables['arabicForms'];
        $laa_array = $tables['lamAlefForms'];
        $diacritics = $tables['diacritics'];
        $endedletter = $tables['nonJoiningBefore'];
        $alfletter = $tables['alefLetters'];
        $punctuation = $tables['punctuation'];

        // paragraph embedding level
        $pel = 0;
        // max level
        $maxlevel = 0;

        // get number of chars
        $numchars = count($ta);

        if ($forcertl == 'R') {
            $pel = 1;
        } elseif ($forcertl == 'L') {
            $pel = 0;
        } else {
            // P2. In each paragraph, find the first character of type L, AL, or R.
            // P3. If a character is found in P2 and it is of type AL or R, then set the paragraph embedding level to one; otherwise, set it to zero.
            for ($i = 0; $i < $numchars; $i++) {
                $type = $unicode[$ta[$i]];
                if ($type == 'L') {
                    $pel = 0;
                    break;
                } elseif (($type == 'AL') or ($type == 'R')) {
                    $pel = 1;
                    break;
                }
            }
        }

        // Current Embedding Level
        $cel = $pel;
        // directional override status
        $dos = 'N';
        $remember = array();
        // start-of-level-run
        $sor = $pel % 2 ? 'R' : 'L';
        $eor = $sor;

        //$levels = array(array('level' => $cel, 'sor' => $sor, 'eor' => '', 'chars' => array()));
        //$current_level = &$levels[count( $levels )-1];

        // Array of characters data
        $chardata = array();

        // X1. Begin by setting the current embedding level to the paragraph embedding level. Set the directional override status to neutral. Process each character iteratively, applying rules X2 through X9. Only embedding levels from 0 to 61 are valid in this phase.
        // 	In the resolution of levels in rules I1 and I2, the maximum embedding level of 62 can be reached.
        for ($i = 0; $i < $numchars; $i++) {
            if ($ta[$i] == BidiControl::RLE) {
                // X2. With each RLE, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = array('num' => BidiControl::RLE, 'cel' => $cel, 'dos' => $dos);
                    $cel = $next_level;
                    $dos = 'N';
                    $sor = $eor;
                    $eor = $cel % 2 ? 'R' : 'L';
                }
            } elseif ($ta[$i] == BidiControl::LRE) {
                // X3. With each LRE, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = array('num' => BidiControl::LRE, 'cel' => $cel, 'dos' => $dos);
                    $cel = $next_level;
                    $dos = 'N';
                    $sor = $eor;
                    $eor = $cel % 2 ? 'R' : 'L';
                }
            } elseif ($ta[$i] == BidiControl::RLO) {
                // X4. With each RLO, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to right-to-left.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = array('num' => BidiControl::RLO, 'cel' => $cel, 'dos' => $dos);
                    $cel = $next_level;
                    $dos = 'R';
                    $sor = $eor;
                    $eor = $cel % 2 ? 'R' : 'L';
                }
            } elseif ($ta[$i] == BidiControl::LRO) {
                // X5. With each LRO, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to left-to-right.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = array('num' => BidiControl::LRO, 'cel' => $cel, 'dos' => $dos);
                    $cel = $next_level;
                    $dos = 'L';
                    $sor = $eor;
                    $eor = $cel % 2 ? 'R' : 'L';
                }
            } elseif ($ta[$i] == BidiControl::PDF) {
                // X7. With each PDF, determine the matching embedding or override code. If there was a valid matching code, restore (pop) the last remembered (pushed) embedding level and directional override.
                if (count($remember)) {
                    $last = count($remember) - 1;
                    // Only explicit embedding and override controls are pushed
                    // onto this stack, so a non-empty stack always has a
                    // matching entry for PDF to pop.
                    $match = array_pop($remember);
                    $cel = $match['cel'];
                    $dos = $match['dos'];
                    $sor = $eor;
                    $eor = $cel % 2 ? 'R' : 'L';
                }
            } elseif (($ta[$i] != BidiControl::RLE) and
                ($ta[$i] != BidiControl::LRE) and
                ($ta[$i] != BidiControl::RLO) and
                ($ta[$i] != BidiControl::LRO) and
                ($ta[$i] != BidiControl::PDF)) {
                // X6. For all types besides RLE, LRE, RLO, LRO, and PDF:
                //	a. Set the level of the current character to the current embedding level.
                //	b. Whenever the directional override status is not neutral, reset the current character type to the directional override status.
                if ($dos != 'N') {
                    $chardir = $dos;
                } else {
                    $chardir = $unicode[$ta[$i]];
                }
                // stores string characters and other information
                $chardata[] = array('char' => $ta[$i], 'level' => $cel, 'type' => $chardir, 'sor' => $sor, 'eor' => $eor);
            }
        } // end for each char

        // X8. All explicit directional embeddings and overrides are completely terminated at the end of each paragraph. Paragraph separators are not included in the embedding.
        // X9. Remove all RLE, LRE, RLO, LRO, PDF, and BN codes.
        // X10. The remaining rules are applied to each run of characters at the same level. For each run, determine the start-of-level-run (sor) and end-of-level-run (eor) type, either L or R. This depends on the higher of the two levels on either side of the boundary (at the start or end of the paragraph, the level of the other run is the base embedding level). If the higher level is odd, the type is R; otherwise, it is L.

        // 3.3.3 Resolving Weak Types
        // Weak types are now resolved one level run at a time. At level run boundaries where the type of the character on the other side of the boundary is required, the type assigned to sor or eor is used.
        // Nonspacing marks are now resolved based on the previous characters.
        $numchars = count($chardata);

        // W1. Examine each nonspacing mark (NSM) in the level run, and change the type of the NSM to the type of the previous character. If the NSM is at the start of the level run, it will get the type of sor.
        $prevlevel = -1; // track level changes
        $levcount = 0; // counts consecutive chars at the same level
        for ($i = 0; $i < $numchars; $i++) {
            if ($chardata[$i]['type'] == 'NSM') {
                if ($levcount) {
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                } elseif ($i > 0) {
                    $chardata[$i]['type'] = $chardata[($i - 1)]['type'];
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W2. Search backward from each instance of a European number until the first strong type (R, L, AL, or sor) is found. If an AL is found, change the type of the European number to Arabic number.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if ($chardata[$i]['char'] == 'EN') {
                for ($j = $levcount; $j >= 0; $j--) {
                    if ($chardata[$j]['type'] == 'AL') {
                        $chardata[$i]['type'] = 'AN';
                    } elseif (($chardata[$j]['type'] == 'L') or ($chardata[$j]['type'] == 'R')) {
                        break;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W3. Change all ALs to R.
        for ($i = 0; $i < $numchars; $i++) {
            if ($chardata[$i]['type'] == 'AL') {
                $chardata[$i]['type'] = 'R';
            }
        }

        // W4. A single European separator between two European numbers changes to a European number. A single common separator between two numbers of the same type changes to that type.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if (($levcount > 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                if (($chardata[$i]['type'] == 'ES') and ($chardata[($i - 1)]['type'] == 'EN') and ($chardata[($i + 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                } elseif (($chardata[$i]['type'] == 'CS') and ($chardata[($i - 1)]['type'] == 'EN') and ($chardata[($i + 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                } elseif (($chardata[$i]['type'] == 'CS') and ($chardata[($i - 1)]['type'] == 'AN') and ($chardata[($i + 1)]['type'] == 'AN')) {
                    $chardata[$i]['type'] = 'AN';
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W5. A sequence of European terminators adjacent to European numbers changes to all European numbers.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if ($chardata[$i]['type'] == 'ET') {
                if (($levcount > 0) and ($chardata[($i - 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                } else {
                    $j = $i + 1;
                    while (($j < $numchars) and ($chardata[$j]['level'] == $prevlevel)) {
                        if ($chardata[$j]['type'] == 'EN') {
                            $chardata[$i]['type'] = 'EN';
                            break;
                        } elseif ($chardata[$j]['type'] != 'ET') {
                            break;
                        }
                        $j++;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W6. Otherwise, separators and terminators change to Other Neutral.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if (($chardata[$i]['type'] == 'ET') or ($chardata[$i]['type'] == 'ES') or ($chardata[$i]['type'] == 'CS')) {
                $chardata[$i]['type'] = 'ON';
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        //W7. Search backward from each instance of a European number until the first strong type (R, L, or sor) is found. If an L is found, then change the type of the European number to L.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if ($chardata[$i]['char'] == 'EN') {
                for ($j = $levcount; $j >= 0; $j--) {
                    if ($chardata[$j]['type'] == 'L') {
                        $chardata[$i]['type'] = 'L';
                    } elseif ($chardata[$j]['type'] == 'R') {
                        break;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // N1. A sequence of neutrals takes the direction of the surrounding strong text if the text on both sides has the same direction. European and Arabic numbers act as if they were R in terms of their influence on neutrals. Start-of-level-run (sor) and end-of-level-run (eor) are used at level run boundaries.
        $prevlevel = -1;
        $levcount = 0;
        for ($i = 0; $i < $numchars; $i++) {
            if (($levcount > 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                if (($chardata[$i]['type'] == 'N') and ($chardata[($i - 1)]['type'] == 'L') and ($chardata[($i + 1)]['type'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                } elseif (($chardata[$i]['type'] == 'N') and
                    (($chardata[($i - 1)]['type'] == 'R') or ($chardata[($i - 1)]['type'] == 'EN') or ($chardata[($i - 1)]['type'] == 'AN')) and
                    (($chardata[($i + 1)]['type'] == 'R') or ($chardata[($i + 1)]['type'] == 'EN') or ($chardata[($i + 1)]['type'] == 'AN'))) {
                    $chardata[$i]['type'] = 'R';
                } elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            } elseif (($levcount == 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                // first char
                if (($chardata[$i]['type'] == 'N') and ($chardata[$i]['sor'] == 'L') and ($chardata[($i + 1)]['type'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                } elseif (($chardata[$i]['type'] == 'N') and
                    ($chardata[$i]['sor'] == 'R') and
                    (($chardata[($i + 1)]['type'] == 'R') or ($chardata[($i + 1)]['type'] == 'EN') or ($chardata[($i + 1)]['type'] == 'AN'))) {
                    $chardata[$i]['type'] = 'R';
                } elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            } elseif (($levcount > 0) and ((($i + 1) == $numchars) or (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] != $prevlevel))) {
                //last char
                if (($chardata[$i]['type'] == 'N') and ($chardata[($i - 1)]['type'] == 'L') and ($chardata[$i]['eor'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                } elseif (($chardata[$i]['type'] == 'N') and
                    (($chardata[($i - 1)]['type'] == 'R') or ($chardata[($i - 1)]['type'] == 'EN') or ($chardata[($i - 1)]['type'] == 'AN')) and
                    ($chardata[$i]['eor'] == 'R')) {
                    $chardata[$i]['type'] = 'R';
                } elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            } elseif ($chardata[$i]['type'] == 'N') {
                // N2. Any remaining neutrals take the embedding direction
                $chardata[$i]['type'] = $chardata[$i]['sor'];
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            } else {
                $levcount++;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // I1. For all characters with an even (left-to-right) embedding direction, those of type R go up one level and those of type AN or EN go up two levels.
        // I2. For all characters with an odd (right-to-left) embedding direction, those of type L, EN or AN go up one level.
        for ($i = 0; $i < $numchars; $i++) {
            $odd = $chardata[$i]['level'] % 2;
            if ($odd) {
                if (($chardata[$i]['type'] == 'L') or ($chardata[$i]['type'] == 'AN') or ($chardata[$i]['type'] == 'EN')) {
                    $chardata[$i]['level'] += 1;
                }
            } else {
                if ($chardata[$i]['type'] == 'R') {
                    $chardata[$i]['level'] += 1;
                } elseif (($chardata[$i]['type'] == 'AN') or ($chardata[$i]['type'] == 'EN')) {
                    $chardata[$i]['level'] += 2;
                }
            }
            $maxlevel = max($chardata[$i]['level'], $maxlevel);
        }

        // L1. On each line, reset the embedding level of the following characters to the paragraph embedding level:
        //	1. Segment separators,
        //	2. Paragraph separators,
        //	3. Any sequence of whitespace characters preceding a segment separator or paragraph separator, and
        //	4. Any sequence of white space characters at the end of the line.
        for ($i = 0; $i < $numchars; $i++) {
            if (($chardata[$i]['type'] == 'B') or ($chardata[$i]['type'] == 'S')) {
                $chardata[$i]['level'] = $pel;
            } elseif ($chardata[$i]['type'] == 'WS') {
                $j = $i + 1;
                while ($j < $numchars) {
                    if ((($chardata[$j]['type'] == 'B') or ($chardata[$j]['type'] == 'S')) or
                        (($j == ($numchars - 1)) and ($chardata[$j]['type'] == 'WS'))) {
                        $chardata[$i]['level'] = $pel;;
                        break;
                    } elseif ($chardata[$j]['type'] != 'WS') {
                        break;
                    }
                    $j++;
                }
            }
        }

        $chardata = ArabicShaper::shape(
            $chardata,
            $unicode,
            $unicode_arlet,
            $laa_array,
            $endedletter,
            $alfletter,
            $punctuation
        );
        $numchars = count($chardata);

        return VisualOrder::reorder($chardata, $unicode_mirror, $maxlevel);
    }
}

