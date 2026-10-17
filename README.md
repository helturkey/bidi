# Arabic BiDi for PHP

A standalone PHP library for converting logical Arabic and mixed-direction text into visual order for renderers that do not implement the Unicode Bidirectional Algorithm (BiDi).

مكتبة PHP مستقلة لمعالجة اتجاه النص العربي والمختلط، للأنظمة التي لا تنفّذ خوارزمية Unicode BiDi بنفسها.

## Important / تنبيه مهم

Browsers, HTML and modern text engines already implement bidirectional rendering. Do not call `Bidi::toVisual()` before displaying text in a browser, or it may be reordered twice.

المتصفحات وHTML ومحركات النصوص الحديثة تدعم عرض الاتجاهين تلقائيًا. لا تستخدم `Bidi::toVisual()` قبل عرض النص في المتصفح حتى لا يُعاد ترتيبه مرتين.

## Features / المميزات

- Internal Unicode Bidirectional Algorithm implementation backed by generated Unicode Character Database tables.
- Optional contextual shaping for common Arabic and Persian letters, intended for legacy renderers.
- Automatic or explicit paragraph direction: auto, left-to-right, or right-to-left.
- UTF-8 validation and Unicode scalar validation at public API boundaries.
- Paragraph analysis with code points, embedding levels and paragraph levels.
- Grapheme-aware wrapping by character clusters (not by pixel width).
- No third-party runtime dependency for BiDi processing.
- The legacy `ArabicUtf8` compatibility API has been removed.

## Requirements / المتطلبات

- PHP 8.2 or later.
- Composer.
- The `mbstring` PHP extension for grapheme-aware wrapping.

## Installation / التثبيت

After the first stable tag is published:

    composer require helturkey/bidi:^1.0

## Quick start / البدء السريع

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\Direction;
use Hussein\Bidirection\TextLayout;

$logical = 'Price: 125.50 USD — السعر';

// Use visual conversion only for a renderer without native BiDi support.
$visual = Bidi::toVisual($logical);

// Force a paragraph direction when required by the output target.
$rtl = Bidi::toVisual('نص عربي', Direction::Rtl);
$ltr = Bidi::toVisual('English text', Direction::Ltr);

// Wraps logical text; this call does not itself perform BiDi reordering.
$wrapped = TextLayout::wrap($logical, width: 24);

echo $visual;
```

## API / الواجهة العامة

### `Bidi::toVisual(string|array $text, Direction|string $direction = Direction::Auto, bool $shapeArabic = true): string|array`

Converts a string or a list of independent paragraphs to visual order. Array entries must be strings and are returned as a numerically indexed list. Direction values are `auto`, `L`/`LTR`, and `R`/`RTL`.

تحوّل نصًا واحدًا أو قائمة من الفقرات المستقلة إلى الترتيب المرئي. يجب أن تكون عناصر المصفوفة نصوصًا، وتُعاد النتائج في قائمة رقمية الفهارس. القيم المقبولة للاتجاه هي `auto` و`L`/`LTR` و`R`/`RTL`.

```php
$visual = Bidi::toVisual('السعر 125 جنيهًا — Price');
$visualParagraphs = Bidi::toVisual([
    'السطر الأول',
    'English line',
    'السعر 125 جنيهًا',
]);

// Disable presentation-form shaping when the renderer shapes Arabic itself.
$unshaped = Bidi::toVisual('سلام', Direction::Rtl, shapeArabic: false);
```

Array input treats each item as a separate paragraph. Do not split one mixed-direction paragraph into display lines and analyze the fragments independently: the resolved embedding levels can depend on the complete paragraph.

### `Bidi::analyze()` and `Bidi::reorderLine()`

For a renderer that chooses its own line breaks, analyze the complete logical paragraph first, then provide each line's matching code-point and resolved-level slices to `reorderLine()`:

```php
$analysis = Bidi::analyze($logicalParagraph, Direction::Rtl);

// After the renderer chooses line boundaries:
$visualLine = Bidi::reorderLine($lineCodePoints, $lineLevels, $paragraphLevel);
```

`BidiAnalysis` exposes `codePoints`, `levels`, `paragraphLevels`, `logicalText()`, `paragraphLevelAt()`, and `visualText`. Code points and levels are indexed by Unicode code point, not UTF-8 byte offset. The caller is responsible for mapping renderer line boundaries back to those indexes.

### `TextLayout::wrap(string $text, int $width = 75, string $break = "\n", bool $cut = false): string`

Wraps logical text based on extended grapheme clusters rather than bytes or pixels. Existing paragraph breaks are retained and CRLF/CR are normalized to LF.

- `width` must be greater than zero.
- `break` must not be empty.
- With `cut: false`, an unbreakable word remains intact when no whitespace exists.
- Invalid UTF-8 and invalid arguments raise `InvalidArgumentException`.

## Unicode algorithm and limitations

The internal engine uses generated Unicode Character Database 18.0.0 property tables for bidirectional classes, joining types, mirroring and paired brackets. The reproducible table generator is `tools/generate-unicode-data.php`; source attribution and applicable Unicode data terms are documented in [UNICODE-LICENSE.md](UNICODE-LICENSE.md).

**Conformance status:** this is an independent implementation under active validation. The project's focused regression tests do not establish full conformance to Unicode Standard Annex #9. Before relying on it for every language and complex control sequence, run and pass the official [`BidiTest.txt`](https://www.unicode.org/Public/18.0.0/ucd/BidiTest.txt) and [`BidiCharacterTest.txt`](https://www.unicode.org/Public/18.0.0/ucd/BidiCharacterTest.txt) suites.

Current limitations:

- Arabic shaping covers common Arabic and Persian letters. It is not a general OpenType shaping engine and does not cover every cursive script.
- Rule L3 combining-mark display behavior and a complete line-layout pipeline are not implemented.
- `TextLayout` wraps logical text by grapheme clusters; it does not resolve BiDi levels or implement the Unicode line-breaking algorithm.
- Visual-order output is intended for legacy renderers. Keep logical text in storage and prefer native BiDi rendering where available.

## Development and tests / التطوير والاختبارات

```sh
composer install
composer validate --strict
composer test
composer analyse
composer audit
```

The suite includes exact visual-order regression cases, directional-level assertions, public-input validation, and generated-Unicode-table integrity checks. See [CONFORMANCE.md](CONFORMANCE.md) for the current conformance status.

## License

The PHP implementation is MIT-licensed. Generated property tables derive from the Unicode Character Database; see [UNICODE-LICENSE.md](UNICODE-LICENSE.md) for attribution and terms.
