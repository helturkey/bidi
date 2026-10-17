# Arabic BiDi for PHP

**Version:** `1.0.x` (the release version is supplied by the Git tag)

A PHP library for preparing Arabic and mixed-direction text for renderers that **do not implement the Unicode Bidirectional Algorithm (BiDi)** themselves.

مكتبة PHP لمعالجة النصوص العربية والنصوص المختلطة التي تحتوي على العربية والإنجليزية والأرقام، للأنظمة التي **لا تدعم خوارزمية Unicode BiDi** داخليًا.

> **Important / تنبيه:** Browsers, HTML, and most modern text-rendering engines already handle BiDi. Do not call `Bidi::toVisual()` before displaying text in a browser; it may reorder the text twice.
>
> المتصفحات وHTML ومعظم محركات عرض النصوص الحديثة تدعم الاتجاهين تلقائيًا. لا تستخدم `Bidi::toVisual()` قبل عرض النص في المتصفح حتى لا يُعاد ترتيب النص مرتين.

## Features / المميزات

- Converts logical text to visual order for legacy renderers. — تحويل النص إلى الترتيب المرئي للأنظمة القديمة.
- Supports a string or an array of strings. — يدعم نصًا واحدًا أو مصفوفة من النصوص.
- Provides Unicode grapheme-aware line wrapping. — يوفّر تقسيم الأسطر مع مراعاة المحارف المركبة.
- Validates UTF-8 in the public conversion API. — يتحقق من صحة UTF-8 في واجهة التحويل العامة.
- Uses a modular, namespaced PHP architecture. — يعتمد بنية PHP مقسّمة إلى وحدات ومساحات أسماء.

## Requirements / المتطلبات

- PHP 8.0 or later. — PHP 8.0 أو أحدث.
- Composer.
- Valid UTF-8 input for `Bidi::toVisual()`. — يجب أن يكون الإدخال صالحًا بترميز UTF-8 عند استخدام `Bidi::toVisual()`.

## Installation / التثبيت

```bash
composer require helturkey/bidi:^1.0
```

## Quick start / البدء السريع

### English example

Use visual conversion only when the destination renderer needs visual-order text, such as a legacy image or PDF drawing API that does not support BiDi itself.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\TextLayout;

$text = 'Price: 125.50 USD — السعر';
$visualText = Bidi::toVisual($text);

$wrappedText = TextLayout::wrap(
    "First line of text\nSecond line of text",
    width: 20,
    break: "\n",
    cut: false,
);

echo $visualText;
echo $wrappedText;
```

### مثال باللغة العربية

استخدم التحويل المرئي فقط إذا كانت وجهة العرض لا تدعم BiDi بنفسها، مثل بعض مكتبات الرسم على الصور أو واجهات PDF القديمة.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Hussein\Bidirection\Bidi;
use Hussein\Bidirection\TextLayout;

$text = 'السعر: 125.50 جنيه — Price';
$visualText = Bidi::toVisual($text);

$wrappedText = TextLayout::wrap(
    "السطر الأول من النص\nالسطر الثاني من النص",
    width: 20,
    break: "\n",
    cut: false,
);

echo $visualText;
echo $wrappedText;
```

The exact visual result depends on the text and renderer. Test mixed Arabic/Latin text, numbers, punctuation, and brackets in your actual output environment.

تعتمد النتيجة المرئية الدقيقة على النص ومحرك العرض. اختبر النصوص المختلطة والأرقام وعلامات الترقيم والأقواس في بيئة العرض الفعلية لديك.

## API reference / شرح الواجهة

### `Bidi::toVisual(string|array $text, bool|string $forceRtl = false): string|array`

Converts one string or an array of strings to visual order.

تحوّل نصًا واحدًا أو مصفوفة نصوص إلى الترتيب المرئي.

```php
use Hussein\Bidirection\Bidi;

// Single string / نص واحد
$visual = Bidi::toVisual('مرحبا بالعالم');

// Array of strings / مصفوفة نصوص
$visualLines = Bidi::toVisual([
    'السطر الأول',
    'English line',
    'السعر 125 جنيهًا',
]);

// Optional direction override / تحديد الاتجاه اختياريًا
$rtl = Bidi::toVisual('نص عربي', 'R');
$ltr = Bidi::toVisual('English text', 'L');
```

The default `false` lets the bundled algorithm determine paragraph direction. `'R'` and `'L'` force right-to-left and left-to-right base direction, respectively.

القيمة الافتراضية `false` تترك للخوارزمية تحديد اتجاه الفقرة. ويمكن استخدام `'R'` لفرض الاتجاه من اليمين إلى اليسار أو `'L'` لفرض الاتجاه من اليسار إلى اليمين.

### `TextLayout::wrap(string $text, int $width = 75, string $break = "\\n", bool $cut = false): string`

Wraps text based on Unicode grapheme clusters, not bytes. This is useful when a renderer accepts a character-count limit but does not provide its own wrapping.

تقسّم النص إلى أسطر اعتمادًا على وحدات المحارف المركبة (grapheme clusters)، وليس على عدد البايتات. وهي مفيدة عندما يفرض محرك العرض حدًا لعدد المحارف ولا يوفّر تقسيمًا للأسطر.

```php
use Hussein\Bidirection\TextLayout;

// Prefer breaking at spaces / يفضّل الفصل عند المسافات
echo TextLayout::wrap('A short English sentence for wrapping.', 12);

// Cut long words if needed / السماح بتقسيم الكلمات الطويلة
echo TextLayout::wrap('مرحبابكمجميعا', 5, "\n", true);

// Use a custom separator / استخدام فاصل مخصص
echo TextLayout::wrap('one two three four', 7, ' | ', false);
```

- `width` must be greater than zero. — يجب أن يكون `width` أكبر من صفر.
- `break` must not be empty. — يجب ألا يكون `break` فارغًا.
- With `cut: false`, long words remain intact when no suitable whitespace is found. — عند `cut: false` تبقى الكلمة الطويلة دون تقسيم إذا لم توجد مسافة مناسبة.
- Width is measured in grapheme clusters, not pixels or terminal columns. — يُحسب العرض بوحدات المحارف المركبة، وليس بالبكسل أو أعمدة الطرفية.
- Invalid UTF-8 or invalid arguments raise `InvalidArgumentException`. — تؤدي البيانات غير الصالحة أو الوسائط غير الصحيحة إلى رمي `InvalidArgumentException`.

## Project structure / بنية المشروع

- `Bidi`: public logical-to-visual conversion API. — واجهة التحويل العامة.
- `Contracts/BidiEngine`: contract for alternate engines. — عقد واجهة لمحركات بديلة.
- `Engine/LegacyBidiEngine`: adapter around the current algorithm. — محوّل إلى الخوارزمية الحالية.
- `Engine/Legacy/BidiAlgorithm`: core bidirectional processing. — المعالجة الأساسية للاتجاهين.
- `Engine/Legacy/ArabicShaper`: Arabic contextual presentation forms. — تشكيل الحروف العربية وفق السياق.
- `Engine/Legacy/VisualOrder`: visual reordering and mirroring. — إعادة الترتيب المرئي وعكس الرموز المناسبة.
- `Data/UnicodeData` and `Data/UnicodeTables`: cached Unicode tables. — جداول Unicode ووسيلة الوصول المخزّنة مؤقتًا.
- `TextLayout`: Unicode-aware line wrapping. — تقسيم الأسطر مع مراعاة Unicode.

The old `ArabicUtf8` compatibility API has been removed. Use `Bidi::toVisual()` and `TextLayout::wrap()` instead.

تم حذف واجهة التوافق القديمة `ArabicUtf8`. استخدم `Bidi::toVisual()` و`TextLayout::wrap()` بدلًا منها.

## Development / التطوير

```bash
composer install
composer validate --strict
composer test
composer analyse
```

## Limitations / حدود الاستخدام

- Do not visually reorder text for HTML/browser output. — لا تعِد ترتيب النص المرئي عند إرساله إلى HTML أو المتصفح.
- Grapheme count does not equal rendered pixel width. Use font metrics for precise image/PDF layout. — عدد المحارف المركبة لا يساوي عرض النص بالبكسل؛ استخدم مقاييس الخط عند الحاجة إلى دقة في الصور وPDF.
- The bundled algorithm has not yet been certified against the complete Unicode BiDi conformance suite. Test your target cases before relying on every script and control character. — لم تُعتمد الخوارزمية بعد باختبارات المطابقة الكاملة لمعيار Unicode BiDi؛ اختبر الحالات المطلوبة قبل الاعتماد عليها مع جميع اللغات ومحارف التحكم.

## License / الترخيص

MIT.
