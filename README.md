![Primitives](banner.png)

This library provides a simple way to convert a string of simple values to their PHP runtime equivalents.

This library will parse the following types of values:

* Numbers
* Strings
* Arrays
* Associative arrays
* true, false, null
* Built-in PHP constants

Unknown types will return `null` as their value.

## Installation

This library can be installed with composer:

```
composer require stillat/primitives
```

## Example Usage

To use the library, create a new instance of the `Parser` class and call the `parseString` method:

```php
<?php

use Stillat\Primitives\Parser;

$parser = new Parser();

$result = $parser->parseString('[1, 2, 3], "some-string", "another", ["one" => 1, "two" => 2]');

```

would produce the following runtime result:

```
array(4) {
  [0] =>
  array(3) {
    [0] =>
    int(1)
    [1] =>
    int(2)
    [2] =>
    int(3)
  }
  [1] =>
  string(11) "some-string"
  [2] =>
  string(7) "another"
  [3] =>
  array(2) {
    'one' =>
    int(1)
    'two' =>
    int(2)
  }
}
```

This library can also parse basic method details using the `parseMethod` method:

```php
<?php

use Stillat\Primitives\Parser;

$parser = new Parser();

$result = $parser->parseMethod('methodName([1, 2, 3])');
```

would produce the following runtime result:

```
array(2) {
  [0] =>
  string(10) "methodName"
  [1] =>
  array(1) {
    [0] =>
    array(3) {
      [0] =>
      int(1)
      [1] =>
      int(2)
      [2] =>
      int(3)
    }
  }
}
```

Invalid input will produce a `null` value.

## License

MIT License. See LICENSE.MD
