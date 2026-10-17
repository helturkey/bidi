# Unicode conformance and data provenance

## Engine

The BiDi engine is implemented in this repository and does not use a third-party runtime library for Unicode bidirectional processing. Property data is generated from Unicode Character Database 18.0.0. The implementation covers explicit levels and overrides, weak and neutral resolution, paired brackets, implicit levels, whitespace resets, visual reordering, and mirroring.

## Validation status

The project has regression tests for the public API, mixed-direction text, isolates, brackets, numbers, Arabic shaping, UTF-8 validation, and wrapping. Full conformance against the official Unicode `BidiTest.txt` and `BidiCharacterTest.txt` suites has not yet been established. Do not treat this implementation as fully UAX #9 conformant until those suites are imported into CI and pass.

Official specification and test data:
- https://www.unicode.org/reports/tr9/
- https://www.unicode.org/Public/18.0.0/ucd/BidiTest.txt
- https://www.unicode.org/Public/18.0.0/ucd/BidiCharacterTest.txt

## Generated data

Run `php tools/generate-unicode-data.php /path/to/ucd` after obtaining the four documented Unicode 18.0.0 UCD files. Generated ranges are stored in `src/Bidirection/Data/UnicodeData.php`. See `UNICODE-LICENSE.md` for source links and attribution.

## Known limitations

- Full official conformance testing remains required.
- Arabic shaping currently maps common Arabic/Persian letters to Unicode Presentation Forms; it is not OpenType shaping and does not cover all cursive scripts.
- Rule L3 combining-mark display behavior and renderer-specific line breaking are not implemented as a complete layout pipeline.
- `TextLayout` wraps logical text by grapheme clusters and does not perform paragraph layout.
- Prefer native BiDi rendering in browsers and modern text engines; visual-order conversion is for legacy output targets.
