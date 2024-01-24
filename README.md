# PHPyh Psalm Tester

Test Psalm via phpt files!

[![Latest Stable Version](https://poser.pugx.org/phpyh/psalm-tester/v/stable.png)](https://packagist.org/packages/phpyh/psalm-tester)
[![Total Downloads](https://poser.pugx.org/phpyh/psalm-tester/downloads.png)](https://packagist.org/packages/phpyh/psalm-tester)
[![psalm-level](https://shepherd.dev/github/phpyh/psalm-tester/level.svg)](https://shepherd.dev/github/phpyh/psalm-tester)
[![type-coverage](https://shepherd.dev/github/phpyh/psalm-tester/coverage.svg)](https://shepherd.dev/github/phpyh/psalm-tester)

## Installation

```shell
composer require --dev phpyh/psalm-tester
```

## Usage

### Write a test in phpt format

`tests/array_values.phpt`

```phpt
--FILE--
<?php

/** @psalm-trace $_list */
$_list = array_values(['a' => 1, 'b' => 2]);

--EXPECT--
Trace on line 9: $_list: non-empty-list<1|2>
```

To avoid hardcoding error details, you can use `EXPECTF`:

```phpt
--EXPECTF--
Trace on line %d: $_list: non-empty-list<%s>
```

### Create a test suite

`tests/PsalmTest.php`

```php
<?php

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use PHPyh\PsalmTester\PsalmTester;
use PHPyh\PsalmTester\StaticAnalysisTest;

final class PsalmTest extends TestCase
{
    private ?PsalmTester $psalmTester = null;

    #[TestWith([__DIR__ . '/array_values.phpt'])]
    public function testPhptFiles(string $phptFile): void
    {
        $this->psalmTester ??= PsalmTester::create();
        $this->psalmTester->test(StaticAnalysisTest::fromPhptFile($phptFile));
    }
}
```
