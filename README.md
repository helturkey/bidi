# Arabic BiDi for PHP

**Version:** `1.0.0`

A PHP library for converting Arabic and mixed-direction text for renderers that do not implement the Unicode Bidirectional Algorithm themselves.

> **Important:** HTML browsers and modern text-layout engines already implement BiDi. Do not visually reorder text before passing it to those renderers, or it may be reordered twice.

## Requirements

- PHP 8.0 or later
- No mandatory runtime Composer dependencies
- Valid UTF-8 input for the new API

## Installation

```bash
composer require helturkey/bidi:^1.0
```

## Backward-compatible API

The existing `Hussein\Bidirection\ArabicUtf8` class remains available:

```php
use Hussein\Bidirection\ArabicUtf8;

$visual = ArabicUtf8::convert('مرحبا بالعالم');
$lines = ArabicUtf8::convert(['السطر الأول', 'السطر الثاني']);

$wrapped = ArabicUtf8::mb_wordwrap($text, 40);
```

Array conversion returns a numerically indexed array, matching the historical behavior.

## New API

```php
use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\TextLayout;

// For a renderer that needs visual-order text:
$visual = Bidi::toVisual('السعر Price: 125.50 USD');

// Width is measured in grapheme clusters, not pixels or terminal columns.
$wrapped = TextLayout::wrap($text, width: 40, cut: false);
```

`Bidi::toVisual()` accepts the legacy direction override as its second argument. The underlying algorithm retains the behavior of the bundled implementation; callers should test representative mixed-script strings for their specific rendering target.

## Unicode wrapping

`TextLayout::wrap()` and `ArabicUtf8::mb_wordwrap()` count grapheme clusters using PCRE's Unicode `\\X` support. They preserve paragraph breaks, validate UTF-8, and throw `InvalidArgumentException` for invalid widths or an empty break string. This is character-based wrapping, not font-metric line layout.

## Architecture

- `Contracts/BidiEngine`: replaceable conversion engine contract.
- `Engine/Legacy/BidiAlgorithm`: namespaced legacy algorithm isolated from the public facade.
- `Data/BidiControl`: namespaced Unicode formatting-control constants.
- `Data/UnicodeTables`: generated Unicode properties returned as a table set, not global variables.
- `Data/UnicodeData`: cached access to the table set.
- `Engine/LegacyBidiEngine`: adapter around the bundled BiDi implementation.
- `Bidi`: new public facade for logical-to-visual conversion.
- `TextLayout`: Unicode-aware wrapping independent of BiDi processing.
- `ArabicUtf8`: compatibility facade for existing consumers.

## Development

```bash
composer install
composer validate --strict
composer test
composer analyse
```

## Limitations

- Visual-order conversion is intended only for legacy renderers that do not implement BiDi.
- Grapheme count is not the same as rendered width. Use the target renderer's font metrics for pixel-perfect wrapping.
- The bundled BiDi implementation is retained for compatibility and should be validated against the Unicode Bidirectional Algorithm conformance tests before claiming full Unicode conformance.

## License

MIT.
