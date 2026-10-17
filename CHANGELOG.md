# Changelog

Notable changes to `helturkey/bidi` are documented here.

## 1.0.0 — 2026-10-17

Initial stable API release of the standalone PHP BiDi implementation.

### Added

- Internal Unicode bidirectional processing without a third-party runtime BiDi dependency.
- Generated Unicode Character Database 18.0.0 tables for bidirectional classes, joining types, mirrored characters, and paired brackets.
- A reproducible Unicode data generator and Unicode data provenance notice.
- Explicit paragraph direction, logical analysis, line reordering, and grapheme-aware text wrapping.
- Optional contextual shaping for common Arabic and Persian letters.
- Regression tests for mixed Arabic/Latin text, numbers, brackets, isolates, explicit controls, API validation, and generated data integrity.
- CI coverage for PHP 8.2, 8.3, 8.4, and 8.5.

### Breaking changes

- Removed the legacy `ArabicUtf8` compatibility API and bundled legacy implementation.
- Introduced the namespaced `Hussein\\Bidirection\\` API.

### Known limitations

- The implementation has not yet passed the complete official Unicode `BidiTest.txt` and `BidiCharacterTest.txt` conformance suites. Do not interpret this release as a claim of full UAX #9 conformance.
- Arabic shaping covers common Arabic and Persian letters; it is not a general OpenType shaping engine.
- Rule L3 combining-mark display behavior and a complete BiDi-aware line-layout pipeline are not implemented.

See [README.md](README.md), [CONFORMANCE.md](CONFORMANCE.md), and [UNICODE-LICENSE.md](UNICODE-LICENSE.md) for usage, current conformance status, and Unicode data terms.
