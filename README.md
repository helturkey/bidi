# Arbic bidirection php package

Arabic bidirection is the best and most accurate php package handling Arabic texts, now you can write text on images easy.

## Installation

Use the php package manager [composer](https://getcomposer.org/) to install.

```bash
composer req helturkey/bidi
```

## Usage

```php
use Hussein\Bidirection\ArabicUtf8;

# returns 'text'

public static function mb_wordwrap(string $string, int $width = 75, $break = "\n", bool $cut = false): string
{
      ........
}

$wrapped = ArabicUtf8::mb_wordwrap($text, $max_chars);


# convert method accepts array or string to convert and returns array or string as passed.

public static function convert(array|string $text, $forcertl = false): array|string
{
      ........
}

$converted = ArabicUtf8::convert($wrapped);

```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
