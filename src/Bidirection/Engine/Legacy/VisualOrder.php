<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine\Legacy;

/**
 * Applies visual reordering and mirrored-glyph substitutions.
 */
final class VisualOrder
{
    /**
     * @param array<int, array<string, mixed>> $chardata
     * @param array<int, int> $unicodeMirror
     * @return array<int, int>
     */
    public static function reorder(array $chardata, array $unicodeMirror, int $maxlevel): array
    {
        $unicode_mirror = $unicodeMirror;
        $numchars = count($chardata);
                // L2. From the highest level found in the text to the lowest odd level on each line, including intermediate levels not actually present in the text, reverse any contiguous sequence of characters that are at that level or higher.
                for ($j = $maxlevel; $j > 0; $j--) {
                    $ordarray = array();
                    $revarr = array();
                    $onlevel = false;
                    for ($i = 0; $i < $numchars; $i++) {
                        if ($chardata[$i]['level'] >= $j) {
                            $onlevel = true;
                            if (isset($unicode_mirror[$chardata[$i]['char']])) {
                                // L4. A character is depicted by a mirrored glyph if and only if (a) the resolved directionality of that character is R, and (b) the Bidi_Mirrored property value of that character is true.
                                $chardata[$i]['char'] = $unicode_mirror[$chardata[$i]['char']];
                            }
                            $revarr[] = $chardata[$i];
                        } else {
                            if ($onlevel) {
                                $revarr = array_reverse($revarr);
                                $ordarray = array_merge($ordarray, $revarr);
                                $revarr = array();
                                $onlevel = false;
                            }
                            $ordarray[] = $chardata[$i];
                        }
                    }
                    if ($onlevel) {
                        $revarr = array_reverse($revarr);
                        $ordarray = array_merge($ordarray, $revarr);
                    }
                    $chardata = $ordarray;
                }
        
                $ordarray = array();
                for ($i = 0; $i < $numchars; $i++) {
                    $ordarray[] = $chardata[$i]['char'];
                }
        
                return $ordarray;
    }
}
