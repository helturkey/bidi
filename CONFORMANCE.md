# Unicode conformance and data provenance

## Engine

The BiDi engine is implemented in this repository and uses generated Unicode Character Database 18.0.0 property tables. The implementation has focused tests for mixed Arabic/Latin runs, digit ordering, bracket mirroring, isolates, explicit formatting controls, resolved levels, input validation and generated-table integrity.

## Validation status

These regression tests are not a substitute for the official Unicode Bidirectional Algorithm conformance suites. Full conformance against `BidiTest.txt` and `BidiCharacterTest.txt` has not yet been established. Do not claim complete UAX #9 conformance until the official tests are integrated and pass.

Official specification and test data:
- https://www.unicode.org/reports/tr9/
- https://www.unicode.org/Public/18.0.0/ucd/BidiTest.txt
- https://www.unicode.org/Public/18.0.0/ucd/BidiCharacterTest.txt

## Generated data

Run `php tools/generate-unicode-data.php /path/to/ucd` after obtaining the Unicode 18.0.0 UCD files listed in the generator header. Generated ranges are stored in `src/Bidirection/Data/UnicodeData.php`. See `UNICODE-LICENSE.md` for provenance and attribution.

## Known limitations

- Official full-suite validation remains outstanding.
- Arabic shaping currently maps common Arabic/Persian letters to Unicode Presentation Forms; it is not OpenType shaping and does not cover all cursive scripts.
- Rule L3 combining-mark display behavior and renderer-specific line breaking are not implemented as a complete layout pipeline.
- `TextLayout` wraps logical text by grapheme clusters and does not perform paragraph layout.
- Prefer native BiDi rendering in browsers and modern text engines; visual-order conversion is for legacy output targets.
