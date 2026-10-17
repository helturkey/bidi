#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generate the compact Unicode tables used by helturkey/bidi.
 *
 * Usage:
 *   php tools/generate-unicode-data.php /path/to/ucd
 *
 * The directory must contain:
 *   extracted/DerivedBidiClass.txt
 *   extracted/DerivedJoiningType.txt
 *   BidiMirroring.txt
 *   BidiBrackets.txt
 *
 * Download these files from https://www.unicode.org/Public/18.0.0/ucd/.
 * This generator stores only ranges/entries that differ from UBA defaults.
 */

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/generate-unicode-data.php /path/to/ucd\n");
    exit(2);
}

$root = rtrim($argv[1], DIRECTORY_SEPARATOR);
$files = [
    'bidi' => $root . '/extracted/DerivedBidiClass.txt',
    'joining' => $root . '/extracted/DerivedJoiningType.txt',
    'mirroring' => $root . '/BidiMirroring.txt',
    'brackets' => $root . '/BidiBrackets.txt',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing required UCD file: {$file}\n");
        exit(2);
    }
}

function parseRange(string $range): array
{
    $parts = explode('..', trim($range));
    $start = hexdec($parts[0]);
    $end = hexdec($parts[1] ?? $parts[0]);

    return [$start, $end];
}

/** @return list<array{int, int, string}> */
function parseProperties(string $path, array $aliases, string $default, string $omit): array
{
    $map = array_fill(0, 0x110000, $default);
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException("Unable to read {$path}");
    }

    foreach ($lines as $line) {
        if (preg_match('/#\s*@missing:\s*([0-9A-F.]+)\s*;\s*([A-Za-z_]+)/i', $line, $m)) {
            [$start, $end] = parseRange($m[1]);
            $value = $aliases[$m[2]] ?? $m[2];
        } elseif (preg_match('/^\s*([0-9A-F.]+)\s*;\s*([A-Za-z_]+)/i', $line, $m)) {
            [$start, $end] = parseRange($m[1]);
            $value = $aliases[$m[2]] ?? $m[2];
        } else {
            continue;
        }

        for ($cp = $start; $cp <= $end; ++$cp) {
            $map[$cp] = $value;
        }
    }

    $ranges = [];
    $start = null;
    $last = null;
    $previous = null;

    for ($cp = 0; $cp <= 0x10FFFF; ++$cp) {
        $value = $map[$cp];

        if ($value === $omit) {
            if ($start !== null) {
                $ranges[] = [$start, $last, $previous];
                $start = $last = $previous = null;
            }

            continue;
        }

        if ($start !== null && $value === $previous && $cp === $last + 1) {
            $last = $cp;
            continue;
        }

        if ($start !== null) {
            $ranges[] = [$start, $last, $previous];
        }

        $start = $last = $cp;
        $previous = $value;
    }

    if ($start !== null) {
        $ranges[] = [$start, $last, $previous];
    }

    return $ranges;
}

$bidiAliases = [
    'Left_To_Right' => 'L', 'Right_To_Left' => 'R', 'Arabic_Letter' => 'AL',
    'European_Number' => 'EN', 'Arabic_Number' => 'AN', 'European_Separator' => 'ES',
    'European_Terminator' => 'ET', 'Common_Separator' => 'CS', 'Nonspacing_Mark' => 'NSM',
    'Boundary_Neutral' => 'BN', 'Paragraph_Separator' => 'B', 'Segment_Separator' => 'S',
    'Whitespace' => 'WS', 'Other_Neutral' => 'ON', 'Left_To_Right_Embedding' => 'LRE',
    'Right_To_Left_Embedding' => 'RLE', 'Left_To_Right_Override' => 'LRO',
    'Right_To_Left_Override' => 'RLO', 'Pop_Directional_Format' => 'PDF',
    'Left_To_Right_Isolate' => 'LRI', 'Right_To_Left_Isolate' => 'RLI',
    'First_Strong_Isolate' => 'FSI', 'Pop_Directional_Isolate' => 'PDI',
];
$joiningAliases = [
    'Non_Joining' => 'U', 'Right_Joining' => 'R', 'Left_Joining' => 'L',
    'Dual_Joining' => 'D', 'Join_Causing' => 'C', 'Transparent' => 'T',
];

$bidi = parseProperties($files['bidi'], $bidiAliases, 'L', 'L');
$joining = parseProperties($files['joining'], $joiningAliases, 'U', 'U');

$mirroring = [];
foreach (file($files['mirroring'], FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    if (preg_match('/^\s*([0-9A-F]+)\s*;\s*([0-9A-F]+)/i', $line, $m)) {
        $mirroring[hexdec($m[1])] = hexdec($m[2]);
    }
}

$brackets = [];
foreach (file($files['brackets'], FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    if (preg_match('/^\s*([0-9A-F]+)\s*;\s*([0-9A-F]+)\s*;\s*([oc])/i', $line, $m)) {
        $brackets[hexdec($m[1])] = [hexdec($m[2]), $m[3]];
    }
}

$version = '18.0.0';
$path = dirname(__DIR__) . '/src/Bidirection/Data/UnicodeData.php';
$renderRanges = static function (array $ranges): string {
    $lines = [];
    foreach ($ranges as [$start, $end, $value]) {
        $lines[] = sprintf("        [0x%X, 0x%X, '%s'],", $start, $end, $value);
    }

    return implode("\n", $lines);
};
$renderMap = static function (array $map, bool $brackets = false): string {
    $lines = [];
    foreach ($map as $cp => $value) {
        if ($brackets) {
            $lines[] = sprintf("        0x%X => [0x%X, '%s'],", $cp, $value[0], $value[1]);
        } else {
            $lines[] = sprintf("        0x%X => 0x%X,", $cp, $value);
        }
    }

    return implode("\n", $lines);
};

$content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Hussein\\Bidirection\\Data;\n\n"
    . "/** Generated from Unicode Character Database {$version}; do not edit manually. */\n"
    . "final class UnicodeData\n{\n    public const VERSION = '{$version}';\n\n"
    . "    /** @var list<array{int, int, string}> */\n    public const BIDI_RANGES = [\n"
    . $renderRanges($bidi) . "\n    ];\n\n"
    . "    /** @var list<array{int, int, string}> */\n    public const JOINING_RANGES = [\n"
    . $renderRanges($joining) . "\n    ];\n\n"
    . "    /** @var array<int, int> */\n    public const MIRRORING = [\n"
    . $renderMap($mirroring) . "\n    ];\n\n"
    . "    /** @var array<int, array{int, string}> */\n    public const BRACKETS = [\n"
    . $renderMap($brackets, true) . "\n    ];\n}\n";

if (file_put_contents($path, $content) === false) {
    fwrite(STDERR, "Unable to write generated file: {$path}\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "Generated %s: %d bidi ranges, %d joining ranges, %d mirrors, %d bracket entries.\n",
    $path,
    count($bidi),
    count($joining),
    count($mirroring),
    count($brackets)
));
