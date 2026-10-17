<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine\Legacy;

/**
 * Applies Arabic contextual presentation forms after bidi levels are resolved.
 */
final class ArabicShaper
{
    /**
     * @param array<int, array<string, mixed>> $chardata
     * @param array<int, string> $unicode
     * @param array<int, array<int, int>> $unicodeArForms
     * @param array<int, array<int, int>> $lamAlefForms
     * @param array<int, int> $nonJoiningBefore
     * @param array<int, int> $alefLetters
     * @param array<int, int> $punctuation
     * @return array<int, array<string, mixed>>
     */
    public static function shape(
        array $chardata,
        array $unicode,
        array $unicodeArForms,
        array $lamAlefForms,
        array $nonJoiningBefore,
        array $alefLetters,
        array $punctuation
    ): array {
        $unicode_arlet = $unicodeArForms;
        $laa_array = $lamAlefForms;
        $endedletter = $nonJoiningBefore;
        $alfletter = $alefLetters;
        $numchars = count($chardata);
                // Cursively connected scripts, such as Arabic or Syriac, require the selection of positional character shapes that depend on adjacent characters. Shaping is logically applied after the Bidirectional Algorithm is used and is limited to characters within the same directional run.
        //        $endedletter = array(1569, 1570, 1571, 1572, 1573, 1575, 1577, 1583, 1584, 1585, 1586, 1608, 1688);
        //        $alfletter = array(1570, 1571, 1573, 1575);
                $chardata2 = $chardata;
                $laaletter = false;
                $charAL = array();
                $x = 0;
                for ($i = 0; $i < $numchars; $i++) {
                    if (($unicode[$chardata[$i]['char']] == 'AL') or ($unicode[$chardata[$i]['char']] == 'WS') || ($unicode[$chardata[$i]['char']] == 'CS')) {
                        $charAL[$x] = $chardata[$i];
                        $charAL[$x]['i'] = $i;
                        $chardata[$i]['x'] = $x;
                        $x++;
                    }
                }
                $numAL = $x;
        
                for ($i = 0; $i < $numchars; $i++) {
                    $thischar = $chardata[$i];
                    if ($i > 0) {
                        $prevchar = $chardata[($i - 1)];
                    } else {
                        $prevchar = false;
                    }
                    if (($i + 1) < $numchars) {
                        $nextchar = $chardata[($i + 1)];
                    } else {
                        $nextchar = false;
                    }
        
                    if ($unicode[$thischar['char']] == 'AL') {
                        $x = $thischar['x'];
                        if ($x > 0) {
                            $prevchar = $charAL[($x - 1)];
                        } else {
                            $prevchar = false;
                        }
                        if (($x + 1) < $numAL) {
                            $nextchar = $charAL[($x + 1)];
                        } else {
                            $nextchar = false;
                        }
                        // if laa letter
                        if (($prevchar !== false) and ($prevchar['char'] == 1604) and (in_array($thischar['char'], $alfletter))) {
                            $arabicarr = $laa_array;
                            $laaletter = true;
                            if ($x > 1) {
                                $prevchar = $charAL[($x - 2)];
                            } else {
                                $prevchar = false;
                            }
                        } else {
                            $arabicarr = $unicode_arlet;
                            $laaletter = false;
                        }
        
                        if (($prevchar !== false) and ($nextchar !== false) and
                            (($unicode[$prevchar['char']] == 'AL') or ($unicode[$prevchar['char']] == 'NSM')) and
                            (($unicode[$nextchar['char']] == 'AL') or ($unicode[$nextchar['char']] == 'NSM')) and
                            ($prevchar['type'] == $thischar['type']) and
                            ($nextchar['type'] == $thischar['type']) and
                            ($nextchar['char'] != 1567)) {
        
                            if ((in_array($prevchar['char'], $punctuation) && isset($arabicarr[$thischar['char']][2]))) {
                                // solve handling charackter with previous punctuation as a middle charackter
                                // should handel as end char. if isset
                                $chardata2[$i]['char'] = $arabicarr[$thischar['char']][2];
                            } else if ((in_array($nextchar['char'], $punctuation) && isset($arabicarr[$thischar['char']][1])) || ($nextchar['char'] === 1569 && $thischar['char'] === 1610)) {
                                // 1569 hamza 1610 ي
                                // end if next punctuations
                                // for example شيء
                                $chardata2[$i]['char'] = $arabicarr[$thischar['char']][1];
                            } else if (in_array($prevchar['char'], $endedletter)) {
                                if (isset($arabicarr[$thischar['char']][2])) {
                                    // initial
                                    $chardata2[$i]['char'] = $arabicarr[$thischar['char']][2];
                                }
                            } else {
                                if (isset($arabicarr[$thischar['char']][3])) {
                                    // medial
                                    $chardata2[$i]['char'] = $arabicarr[$thischar['char']][3];
                                }
                            }
                        } elseif (($nextchar !== false) and
                            (($unicode[$nextchar['char']] == 'AL') or ($unicode[$nextchar['char']] == 'NSM')) and
                            ($nextchar['type'] == $thischar['type']) and
                            ($nextchar['char'] != 1567)) {
                            if (isset($arabicarr[$chardata[$i]['char']][2])) {
                                // initial
                                $chardata2[$i]['char'] = $arabicarr[$thischar['char']][2];
                            }
                        } elseif ((($prevchar !== false) and
                                (($unicode[$prevchar['char']] == 'AL') or ($unicode[$prevchar['char']] == 'NSM')) and
                                ($prevchar['type'] == $thischar['type'])) or
                            (($nextchar !== false) and ($nextchar['char'] == 1567))) {
                            // final
                            if (($i > 1) and ($thischar['char'] == 1607) and
                                ($chardata[$i - 1]['char'] == 1604) and
                                ($chardata[$i - 2]['char'] == 1604) and (isset($chardata[$i - 3]['char']) && $chardata[$i - 3]['char'] == 1575)) {
                                //Allah Word
                                // mark characters to delete with false
                                // solve 2 ا alef in writing الله
                                $chardata2[$i - 3]['char'] = false;
        
                                $chardata2[$i - 2]['char'] = false;
                                $chardata2[$i - 1]['char'] = false;
                                $chardata2[$i]['char'] = 65010;
                            } else {
                                if (($prevchar !== false) and in_array($prevchar['char'], $endedletter)) {
                                    if (isset($arabicarr[$thischar['char']][0])) {
                                        // isolated
                                        $chardata2[$i]['char'] = $arabicarr[$thischar['char']][0];
                                    }
                                } else {
                                    if (isset($arabicarr[$thischar['char']][1])) {
                                        // final
                                        $chardata2[$i]['char'] = $arabicarr[$thischar['char']][1];
                                    }
                                }
                            }
                        } elseif (isset($arabicarr[$thischar['char']][0])) {
                            // isolated
                            $chardata2[$i]['char'] = $arabicarr[$thischar['char']][0];
                        }
                        // if laa letter
                        if ($laaletter) {
                            // mark characters to delete with false
                            $chardata2[($charAL[($x - 1)]['i'])]['char'] = false;
                        }
                    } // end if AL (Arabic Letter)
                } // end for each char
        
                // remove marked characters
                foreach ($chardata2 as $key => $value) {
                    if ($value['char'] === false) {
                        unset($chardata2[$key]);
                    }
                }
                $chardata = array_values($chardata2);
                $numchars = count($chardata);
                unset($chardata2);
                unset($arabicarr);
                unset($laaletter);
                unset($charAL);
        
        
        return $chardata;
    }
}
