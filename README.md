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

## Parsing Nested Methods

A more advanced alternative of `parseMethod` is the `parseMethods` method:

```php
use Stillat\Primitives\Parser;

$parser = new Parser();

$result = $parser->parseMethods("randomElements(['a', 'b', 'c', 'd', 'e'], rand(1, 5))"); 
```

Detected method calls will be returned as instances of `Stillat\Primitives\MethodCall`. Each instance of this class
will contain the original method's name, as well as the parsed (and evaluated) runtime arguments. `parseMethods` will
**not** run any methods for you.

## Executing Runtime Methods

Primitives provides a utility `MethodRunner` class that can be used to execute the results of the `parseMethods` on any
target class:

```php
<?php

use Stillat\Primitives\Parser;
use Stillat\Primitives\MethodRunner;

$parser = new Parser();
$runner = new MethodRunner();

class MyClass {

    public function sayHello($name)
    {
        return 'Hello, '.$name;
    }

}

$myClassInstance = new MyClass();

$methods = $parser->parseMethods("sayHello('Dave')");
$result = $runner->run($methods, $myClassInstance);

```

After the above code has executed, `$result` would contain the value `Hello, Dave`.

Important notes when using `MethodRunner`:

* There must only be one root method call
* If there is more than one root element, the `run` method returns `null`
* `MethodRunner` does not check for method existence, allowing `__call` to be invoked

## Calling Native PHP Functions

The internal method runner does not support calling native PHP functions. However, we can create a class instance
that can (and utilize whatever logic is appropriate for the current project to determine what is a "safe" function to call):

```php
<?php

use Stillat\Primitives\Parser;
use Stillat\Primitives\MethodRunner;

$parser = new Parser();
$runner = new MethodRunner();

class Greeter {

    public function sayHello($name)
    {
        return 'Hello, '.$name;
    }

}

class MethodTarget
{

    protected $instance;
    protected $safePhpFunctions = [
        'strtoupper'
    ];

    public function __construct()
    {
        $this->instance = new Greeter();
    }

    public function __call($name, $arguments)
    {
        // Replace with whatever logic makes sense. This approach
        // utilizes an allowed list of functions, but using
        // something like function_exists also works.
        if (in_array($name, $this->safePhpFunctions)) {
            return call_user_func($name, ...$arguments);
        }

        return call_user_func([$this->instance, $name], ...$arguments);
    }

}

$instance = new MethodTarget();

$result = $parser->parseMethods('sayHello(strtoupper("this is lowercase"))');

$methodResult = $runner->run($result, $instance);

```

After the above code has executed, `$methodResult` would contain the value `Hello, THIS IS LOWERCASE`. This approach works
because we are making use of PHP's `__call` magic method to perform method overloading. When we attempt to call a method
on our class instance that does not exist, the `__call` method will receive the method name and arguments. If the list
of safe functions contains the incoming method name, we will invoke it and return the results with the original arguments.
If our safe list does not contain the function, we default to attempting to call it on our target class instance.

## Context Variables

You may also supply an array of contextual data that can be used when evaluating the input string. Context variables
utilize the `$` syntax. The variable name in the input string will be replaced with their actual values once evaluated:

```php
<?php

use Stillat\Primitives\Parser;

$parser = new Parser();

$context = [
    'name' => 'Dave',
    'city' => 'Anywhere'
];

$result = $parser->parseString('[$name, $city]', $context);
```

Once the previous example has executed, `$result` would contain a value similar to:

```
array(1) {
  [0] =>
  array(2) {
    [0] =>
    string(4) "Dave"
    [1] =>
    string(8) "Anywhere"
  }
}
```

Nested variable paths can be utilized by using PHP's property fetcher syntax (array accessor syntax is not supported):

```php
<?php

use Stillat\Primitives\Parser;

$parser = new Parser();

$context = [
    'nested' => [
        'arrays' => [
            'test' => [
                'name' => 'Dave',
                'city' => 'Anywhere'
            ]
        ]
    ]
];

$result = $parser->parseString('[$nested->arrays->test->name,' .
    '$nested->arrays->test->city]', $context);
```

Like before, the `$result` variable would contain a value similar to the following:

```
array(1) {
  [0] =>
  array(2) {
    [0] =>
    string(4) "Dave"
    [1] =>
    string(8) "Anywhere"
  }
}
```

## License

MIT License. See LICENSE.MD
