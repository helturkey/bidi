<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Support;

use InvalidArgumentException;

final class Unicode
{
    private function __construct() {}

    public static function assertValidUtf8(string $text): void
    {
        if (preg_match('//u', $text) !== 1) { throw new InvalidArgumentException('Text must be valid UTF-8.'); }
    }

    /** @return list<int> */
    public static function toCodePoints(string $text): array
    {
        self::assertValidUtf8($text); $out=[]; $n=strlen($text);
        for ($i=0;$i<$n;) {
            $a=ord($text[$i++]);
            if ($a<0x80) { $out[]=$a; continue; }
            if ($a<0xE0) { $b=ord($text[$i++]); $out[]=(($a&31)<<6)|($b&63); continue; }
            if ($a<0xF0) { $b=ord($text[$i++]); $c=ord($text[$i++]); $out[]=(($a&15)<<12)|(($b&63)<<6)|($c&63); continue; }
            $b=ord($text[$i++]); $c=ord($text[$i++]); $d=ord($text[$i++]);
            $out[]=(($a&7)<<18)|(($b&63)<<12)|(($c&63)<<6)|($d&63);
        }
        return $out;
    }

    /** @param list<int> $points */
    public static function fromCodePoints(array $points): string
    {
        $out='';
        foreach ($points as $cp) {
            if ($cp<0||$cp>0x10FFFF||($cp>=0xD800&&$cp<=0xDFFF)) throw new InvalidArgumentException('Invalid Unicode scalar value.');
            if ($cp<0x80) $out.=chr($cp);
            elseif ($cp<0x800) $out.=chr(0xC0|($cp>>6)).chr(0x80|($cp&63));
            elseif ($cp<0x10000) $out.=chr(0xE0|($cp>>12)).chr(0x80|(($cp>>6)&63)).chr(0x80|($cp&63));
            else $out.=chr(0xF0|($cp>>18)).chr(0x80|(($cp>>12)&63)).chr(0x80|(($cp>>6)&63)).chr(0x80|($cp&63));
        }
        return $out;
    }

    /** @return list<string> */
    public static function graphemes(string $text): array
    {
        self::assertValidUtf8($text);
        if ($text==='') return [];
        $count=preg_match_all('/\X/u',$text,$matches);
        if ($count===false) throw new InvalidArgumentException('Unable to segment the UTF-8 string.');
        return $matches[0];
    }
}
