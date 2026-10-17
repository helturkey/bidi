<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Engine;

use Hussein\Bidirection\BidiAnalysis;
use Hussein\Bidirection\Contracts\BidiEngine;
use Hussein\Bidirection\Data\UnicodeData;
use Hussein\Bidirection\Direction;
use Hussein\Bidirection\Support\Unicode;
use InvalidArgumentException;

/**
 * Internal UAX #9 engine. Unicode property data is generated from the UCD.
 *
 * Implements explicit levels, weak/neutral resolution, paired brackets,
 * implicit levels, L1 resets, L2 reordering and mirrored punctuation.
 */
final class UnicodeBidiEngine implements BidiEngine
{
    public function toVisual(string $text, Direction $direction = Direction::Auto, bool $shapeArabic = true): string
    {
        return $this->analyze($text, $direction, $shapeArabic)->visualText;
    }

    public function analyze(string $text, Direction $direction = Direction::Auto, bool $shapeArabic = true): BidiAnalysis
    {
        $points = Unicode::toCodePoints($text);
        if ($points === []) { return new BidiAnalysis([], [], [], ''); }

        $visual = $logical = $levelsOut = $paragraphs = [];
        $start = 0; $n = count($points);
        while ($start < $n) {
            $end = $start;
            while ($end < $n && self::type($points[$end]) !== 'B') { ++$end; }
            $part = array_slice($points, $start, $end - $start);
            [$vp, $lp, $lev, $base] = $this->paragraph($part, $direction);
            array_push($visual, ...$vp);
            array_push($logical, ...$lp);
            array_push($levelsOut, ...$lev);
            array_push($paragraphs, ...array_fill(0, count($lp), $base));
            if ($end < $n) {
                $visual[] = $points[$end]; $logical[] = $points[$end];
                $levelsOut[] = 0; $paragraphs[] = 0; $start = $end + 1;
            } else { $start = $n; }
        }
        if ($shapeArabic) { $visual = ArabicShaper::shape($visual); }
        return new BidiAnalysis($logical, $levelsOut, $paragraphs, Unicode::fromCodePoints($visual));
    }

    /** @param array<array-key, mixed> $codePoints @param array<array-key, mixed> $levels */
    public function reorderLine(array $codePoints, array $levels, int $paragraphLevel): string
    {
        if (count($codePoints) !== count($levels)) { throw new InvalidArgumentException('Code points and levels must have the same length.'); }
        if ($paragraphLevel !== 0 && $paragraphLevel !== 1) { throw new InvalidArgumentException('Paragraph level must be 0 or 1.'); }
        foreach ($codePoints as $cp) {
            if (!is_int($cp) || $cp < 0 || $cp > 0x10FFFF || ($cp >= 0xD800 && $cp <= 0xDFFF)) {
                throw new InvalidArgumentException('Every code point must be a Unicode scalar value.');
            }
        }
        foreach ($levels as $level) {
            if (!is_int($level) || $level < 0 || $level > 125) { throw new InvalidArgumentException('Every embedding level must be between 0 and 125.'); }
        }
        [$points, $orderedLevels] = self::reorderPairs(array_values($codePoints), array_values($levels), $paragraphLevel);
        foreach ($points as $i => $cp) {
            if ($orderedLevels[$i] % 2 === 1) { $points[$i] = UnicodeData::MIRRORING[$cp] ?? $cp; }
        }
        return Unicode::fromCodePoints($points);
    }

    /** @param list<int> $points @return array{list<int>,list<int>,list<int>,int} */
    private function paragraph(array $points, Direction $requested): array
    {
        $base = $requested === Direction::Ltr ? 0 : ($requested === Direction::Rtl ? 1 : self::firstStrong($points));
        $types = array_map(self::type(...), $points);
        $levels = array_fill(0, count($points), $base);
        $stack = [[$base, null, false]];
        $removed = [];
        $overflowEmbedding = 0; $overflowIsolate = 0; $validIsolates = 0;

        // X1-X8. Isolate initiators and PDI establish stack boundaries.
        foreach ($types as $i => $type) {
            $top = $stack[count($stack)-1]; $levels[$i] = $top[0];
            if (in_array($type, ['RLE','LRE','RLO','LRO'], true)) {
                $new = in_array($type, ['RLE','RLO'], true) ? (($top[0] + 1) | 1) : (($top[0] + 2) & ~1);
                if ($new <= 125 && $overflowIsolate === 0 && $overflowEmbedding === 0) {
                    $stack[] = [$new, in_array($type, ['RLO','LRO'], true) ? ($type[0] === 'R' ? 'R' : 'L') : null, false];
                } elseif ($overflowIsolate === 0) { ++$overflowEmbedding; }
                $removed[$i] = true;
            } elseif ($type === 'PDF') {
                if ($overflowIsolate === 0 && $overflowEmbedding > 0) { --$overflowEmbedding; }
                elseif ($overflowIsolate === 0 && count($stack) > 1 && !$top[2]) { array_pop($stack); }
                $removed[$i] = true;
            } elseif (in_array($type, ['LRI','RLI','FSI'], true)) {
                $dir = $type === 'LRI' ? 0 : ($type === 'RLI' ? 1 : self::firstStrong(array_slice($points, $i+1)));
                $new = $dir ? (($top[0]+1)|1) : (($top[0]+2)&~1);
                if ($new <= 125 && $overflowIsolate === 0 && $overflowEmbedding === 0) {
                    $stack[] = [$new, null, true]; ++$validIsolates;
                } else { ++$overflowIsolate; }
            } elseif ($type === 'PDI') {
                if ($overflowIsolate > 0) { --$overflowIsolate; }
                elseif ($validIsolates > 0) {
                    $overflowEmbedding = 0;
                    while (count($stack) > 1) { $entry = array_pop($stack); if ($entry[2]) { break; } }
                    --$validIsolates;
                }
                $levels[$i] = $stack[count($stack)-1][0];
            } else {
                if ($top[1] !== null && !in_array($type, ['B','S','BN'], true)) { $types[$i] = $top[1]; }
            }
        }

        // X9 removes explicit embedding controls and BN from resolution/output.
        $p = $t = $l = [];
        foreach ($points as $i => $cp) {
            if (isset($removed[$i]) || $types[$i] === 'BN') { continue; }
            $p[] = $cp; $t[] = $types[$i]; $l[] = $levels[$i];
        }
        if ($p === []) { return [[], [], [], $base]; }

        // W1-W7.
        $sos = max($base, $l[0]) % 2 ? 'R' : 'L';
        $prev = $sos;
        foreach ($t as $i => $v) {
            if ($v === 'NSM') { $t[$i] = $prev; }
            elseif (!in_array($v, ['BN'], true)) { $prev = $v; }
        }
        $strong = $sos;
        foreach ($t as $i => $v) {
            if (in_array($v, ['L','R','AL'], true)) { $strong = $v; }
            elseif ($v === 'EN' && $strong === 'AL') { $t[$i] = 'AN'; }
        }
        foreach ($t as $i => $v) { if ($v === 'AL') { $t[$i] = 'R'; } }
        for ($i=1; $i+1<count($t); ++$i) {
            if ($t[$i] === 'ES' && $t[$i-1] === 'EN' && $t[$i+1] === 'EN') { $t[$i]='EN'; }
            if ($t[$i] === 'CS' && $t[$i-1] === $t[$i+1] && in_array($t[$i-1], ['EN','AN'], true)) { $t[$i]=$t[$i-1]; }
        }
        foreach ($t as $i => $v) {
            if ($v !== 'ET') { continue; }
            if (($i > 0 && $t[$i-1] === 'EN') || ($i+1<count($t) && $t[$i+1] === 'EN')) { $t[$i]='EN'; }
        }
        foreach ($t as $i => $v) { if (in_array($v, ['ES','ET','CS'], true)) { $t[$i]='ON'; } }
        $strong = $sos;
        foreach ($t as $i => $v) {
            if (in_array($v, ['L','R'], true)) { $strong=$v; }
            elseif ($v === 'EN' && $strong === 'L') { $t[$i]='L'; }
        }

        // N0: paired brackets.
        $opens = [];
        foreach ($p as $i => $cp) {
            $bracket = UnicodeData::BRACKETS[$cp] ?? null;
            if ($bracket === null) { continue; }
            [$mate,$kind] = $bracket;
            if ($kind === 'o') { $opens[] = [$i,$mate]; continue; }
            for ($j=count($opens)-1; $j>=0; --$j) {
                if ($opens[$j][1] !== $cp) { continue; }
                $a=$opens[$j][0]; $embedding=$l[$a]%2 ? 'R':'L'; $opposite=$embedding==='R'?'L':'R';
                $inside=false; $opposed=false;
                for ($k=$a+1; $k<$i; ++$k) {
                    $st=in_array($t[$k],['EN','AN'],true)?'R':$t[$k];
                    if ($st===$embedding) { $inside=true; } if ($st===$opposite) { $opposed=true; }
                }
                if ($inside) { $t[$a]=$t[$i]=$embedding; }
                elseif ($opposed) {
                    $before=$a>0 ? (in_array($t[$a-1],['EN','AN'],true)?'R':$t[$a-1]) : $sos;
                    if ($before===$opposite) { $t[$a]=$t[$i]=$opposite; }
                }
                array_splice($opens,$j); break;
            }
        }

        // N1/N2.
        $neutral=['B','S','WS','ON','LRI','RLI','FSI','PDI'];
        for ($i=0; $i<count($t);) {
            if (!in_array($t[$i],$neutral,true)) { ++$i; continue; }
            $a=$i; while ($i<count($t) && in_array($t[$i],$neutral,true)) { ++$i; }
            $before=$a>0 ? self::strongType($t[$a-1]) : $sos;
            $after=$i<count($t) ? self::strongType($t[$i]) : (($l[$i-1]%2)?'R':'L');
            $resolved=$before===$after ? $before : (($l[$a]%2)?'R':'L');
            for ($j=$a; $j<$i; ++$j) { $t[$j]=$resolved; }
        }

        // I1/I2.
        foreach ($l as $i => $level) {
            if ($level%2===0) {
                if ($t[$i]==='R') { ++$l[$i]; }
                elseif (in_array($t[$i],['AN','EN'],true)) { $l[$i]+=2; }
            } elseif (in_array($t[$i],['L','AN','EN'],true)) { ++$l[$i]; }
        }
        // L1 reset trailing whitespace and whitespace before separators.
        for ($i=count($t)-1; $i>=0 && in_array($t[$i],['WS','BN','S','B','LRI','RLI','FSI','PDI'],true); --$i) { $l[$i]=$base; }
        foreach ($t as $i => $v) {
            if (in_array($v,['B','S'],true)) {
                $l[$i]=$base;
                for ($j=$i-1; $j>=0 && in_array($t[$j],['WS','BN'],true); --$j) { $l[$j]=$base; }
            }
        }

        [$visual,$visualLevels]=self::reorderPairs($p,$l,$base);
        foreach ($visual as $i=>$cp) { if ($visualLevels[$i]%2===1) { $visual[$i]=UnicodeData::MIRRORING[$cp]??$cp; } }
        return [$visual,$p,$l,$base];
    }

    /** @param list<int> $points @return int */
    private static function firstStrong(array $points): int
    {
        foreach ($points as $cp) { $t=self::type($cp); if ($t==='L') return 0; if ($t==='R'||$t==='AL') return 1; }
        return 0;
    }

    private static function type(int $cp): string
    {
        $r=UnicodeData::BIDI_RANGES; $lo=0; $hi=count($r)-1;
        while ($lo<=$hi) {
            $m=intdiv($lo+$hi,2); [$a,$b,$v]=$r[$m];
            if ($cp<$a) $hi=$m-1; elseif ($cp>$b) $lo=$m+1; else return $v;
        }
        return 'L';
    }

    private static function strongType(string $type): string
    {
        if (in_array($type,['EN','AN'],true)) return 'R';
        return in_array($type,['L','R'],true)?$type:'ON';
    }

    /** @param list<int> $points @param list<int> $levels @return array{list<int>,list<int>} */
    private static function reorderPairs(array $points,array $levels,int $base): array
    {
        $max=$levels===[]?$base:max($levels); $odd=array_values(array_filter($levels,static fn(int $x):bool=>$x%2===1));
        if ($odd===[]) return [$points,$levels];
        $minOdd=min($odd);
        for ($level=$max;$level>=$minOdd;--$level) {
            $i=0;
            while ($i<count($points)) {
                if ($levels[$i]<$level) { ++$i; continue; }
                $a=$i; while ($i<count($points)&&$levels[$i]>=$level) { ++$i; }
                $len=$i-$a;
                if ($len>1) {
                    $points=array_merge(array_slice($points,0,$a),array_reverse(array_slice($points,$a,$len)),array_slice($points,$i));
                    $levels=array_merge(array_slice($levels,0,$a),array_reverse(array_slice($levels,$a,$len)),array_slice($levels,$i));
                }
            }
        }
        return [$points,$levels];
    }
}
