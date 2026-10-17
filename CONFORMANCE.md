# Unicode conformance and dependency provenance

## BiDi engine

This package delegates the Unicode Bidirectional Algorithm implementation and Arabic shaping to `tecnickcom/tc-lib-unicode`, constrained in Composer to stable release `^3.0.9`. The upstream project states that it verifies its implementation against the official Unicode `BidiTest.txt` and `BidiCharacterTest.txt` suites:

- Upstream source and test information: https://github.com/tecnickcom/tc-lib-unicode
- Unicode UAX #9: https://www.unicode.org/reports/tr9/
- Official Unicode test-data index: https://www.unicode.org/reports/tr41/

The associated character-property data package identifies Unicode Character Database 17.0.0 for release 3.0.9. The dependency contains the normative property tables and algorithm implementation; this repository does not duplicate or hand-edit those tables.

## What this repository tests

The project CI tests the public adapter, direction validation, UTF-8 rejection, maximum scalar acceptance, mixed-direction output, isolates, brackets/numbers, array input, and grapheme-aware wrapping across the supported PHP matrix.

These tests are integration/regression tests for this wrapper; they are not a copy of the full Unicode conformance datasets. The full UAX #9 conformance claim belongs to the upstream engine version and should be rechecked when upgrading that dependency.

## Known limits

The upstream stable engine documents that L3 combining-mark display behavior is not implemented and that Arabic is the supported contextual-shaping script. This project records those limitations rather than claiming complete layout-engine behavior for all Unicode scripts. `TextLayout` does not resolve BiDi embedding levels and should not be used as a replacement for a renderer's paragraph/line layout process.
