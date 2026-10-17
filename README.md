# Arabic BiDi for PHP

A PHP library for converting logical Arabic and mixed-direction text into visual order for renderers that do not implement the Unicode Bidirectional Algorithm (BiDi) themselves.

مكتبة PHP لتحويل النصوص العربية والمختلطة بين العربية والإنجليزية والأرقام إلى الترتيب المرئي للأنظمة التي لا تنفّذ خوارزمية Unicode BiDi بنفسها.

## Important / تنبيه مهم

Browsers, HTML and modern text engines already implement bidirectional rendering. Do not call `Bidi::toVisual()` before displaying text in a browser, or it may be reordered twice.

المتصفحات وHTML ومحركات عرض النصوص الحديثة تدعم الاتجاهين تلقائيًا. لا تستخدم `Bidi::toVisual()` قبل عرض النص في المتصفح حتى لا يُعاد ترتيبه مرتين.

## Features / المميزات

- Unicode Bidirectional Algorithm processing is implemented internally using generated Unicode Character Database property tables. Common Arabic contextual forms are handled by a separate internal shaper.
- Explicit paragraph direction: auto, left-to-right, or right-to-left.
- Optional Arabic contextual shaping for legacy renderers.
- Safe UTF-8 validation at the public API boundary.
- Grapheme-aware wrapping for text that needs a character-count limit.
- Namespaced, modular PHP API; the old `ArabicUtf8` compatibility API and bundled legacy algorithm have been removed.

## Requirements / المتطلبات

- PHP 8.2 or later.
- Composer.
- The `mbstring` PHP extension (used for grapheme-aware wrapping).

## Installation / التثبيت

After the first stable tag is published:

    composer require helturkey/bidi:^1.0

## Quick start / البدء السريع

    <?php

    require __DIR__ . '/vendor/autoload.php';

    use Hussein\Bidirection\Bidi;
    use Hussein\Bidirection\Direction;
    use Hussein\Bidirection\TextLayout;

    $logical = 'Price: 125.50 USD — السعر';

    // Only for a renderer that does not implement BiDi.
    $visual = Bidi::toVisual($logical);

    // Force paragraph direction when the target needs it.
    $rtl = Bidi::toVisual('نص عربي', Direction::Rtl);
    $ltr = Bidi::toVisual('English text', Direction::Ltr);

    // This wraps logical text; it does not itself reorder BiDi text.
    $wrapped = TextLayout::wrap($logical, width: 24);

    echo $visual;

## API / الواجهة العامة

### `Bidi::toVisual(string|array $text, Direction|string $direction = Direction::Auto, bool $shapeArabic = true): string|array`

Converts a string or a list of independent lines to visual order. Array entries must be strings; results are returned as a numerically indexed list. Recognized direction values are `auto`, `L`/`LTR`, and `R`/`RTL`.

تحوّل نصًا واحدًا أو قائمة من الأسطر المستقلة إلى الترتيب المرئي. يجب أن تكون عناصر المصفوفة نصوصًا، وتُعاد النتائج في قائمة رقمية الفهارس. القيم المقبولة للاتجاه هي `auto` و`L`/`LTR` و`R`/`RTL`.

    $visual = Bidi::toVisual('السعر 125 جنيهًا — Price');
    $visualLines = Bidi::toVisual([
        'السطر الأول',
        'English line',
        'السعر 125 جنيهًا',
    ]);
    $unshaped = Bidi::toVisual('سلام', Direction::Rtl, shapeArabic: false);

When a paragraph wraps into display lines, wrapping and BiDi processing are not interchangeable. This package's TextLayout utility wraps logical text only; it does not calculate resolved embedding levels for each line. Array input treats each item as an independent paragraph, not as line fragments of a single resolved paragraph.

عند التفاف الفقرة إلى أسطر للعرض، لا يمكن اعتبار تقسيم الأسطر ومعالجة BiDi عمليتين متبادلتين. أداة TextLayout تقسّم النص المنطقي فقط ولا تحسب مستويات الاتجاه المحسومة لكل سطر. تُعامل عناصر المصفوفة كفقرات مستقلة، وليس كأسطر مقتطعة من فقرة واحدة.

### `TextLayout::wrap(string $text, int $width = 75, string $break = "\n", bool $cut = false): string`

Wraps text based on Unicode grapheme clusters, not bytes or pixels. Existing paragraph breaks are retained and CRLF/CR are normalized to LF.

- `width` must be greater than zero.
- `break` must not be empty.
- With `cut: false`, an unbreakable word remains intact when no whitespace exists.
- Invalid UTF-8 and invalid arguments raise `InvalidArgumentException`.

تقسّم النص اعتمادًا على وحدات المحارف المركبة، وليس البايتات أو البكسلات. تُحافظ على فواصل الفقرات الحالية ويُوحّد CRLF وCR إلى LF.

### Paragraph analysis and line reordering

For renderers that choose their own line breaks, analyze the full logical paragraph first, break it using the renderer's line layout, then reorder each line using the matching code-point and level slices:

    $analysis = Bidi::analyze($logicalParagraph, Direction::Rtl);
    // After the renderer chooses line boundaries:
    $visualLine = Bidi::reorderLine($lineCodePoints, $lineLevels, $paragraphLevel);

BidiAnalysis exposes codePoints, levels, and paragraphLevels aligned by code point, plus logicalText() and paragraphLevelAt(). The caller is responsible for mapping line boundaries back to analyzed code-point offsets. Do not wrap a paragraph first and analyze each fragment independently if the fragments belong to one paragraph.

## Unicode algorithm and limitations

The algorithm is provided by `tecnickcom/tc-lib-unicode` version 3.2.2 or a compatible stable 3.x release. That project documents its BiDi engine as verified against the official `BidiTest.txt` and `BidiCharacterTest.txt` suites. The Unicode property data comes from `tecnickcom/tc-lib-unicode-data`; version 3.0.9 identifies Unicode Character Database 17.0.0.

This package no longer maintains its own hand-written BiDi algorithm or generated Unicode tables. See [the upstream algorithm](https://github.com/tecnickcom/tc-lib-unicode) and [UAX #9](https://www.unicode.org/reports/tr9/) for algorithm scope.

Current implementation scope and limitations:

- Rule L3 (combining-mark display behavior) is not implemented by the upstream engine.
- Contextual shaping is provided for Arabic, not every cursive script (such as Syriac, N'Ko, Mandaic and Adlam).
- Paragraph wrapping and visual ordering must be coordinated by the host renderer. The TextLayout helper is not a full Unicode line-breaking engine.
- Visual-order text is intended for legacy output only; keep logical text in storage and use native BiDi rendering whenever possible.

For production systems, test the actual output path with mixed Arabic/Latin runs, URLs, currency and decimal numbers, paired brackets, explicit isolate controls, combining marks, and paragraph separators.

## Development / التطوير

    composer install
    composer validate --strict
    composer test
    composer analyse
    composer audit

## Licensing

The PHP implementation is MIT-licensed. Generated Unicode property tables are derived from the Unicode Character Database; see [UNICODE-LICENSE.md](UNICODE-LICENSE.md) for attribution and applicable Unicode terms.

## License

MIT. See LICENSE for this package's own source.
