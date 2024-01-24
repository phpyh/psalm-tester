# PHPyh Psalm Tester

## Installation

```shell
composer require --dev phpyh/psalm-tester
```

## Usage

### Write a test in phpt format

tests/array_values.phpt
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

tests/PsalmTest.php
```php
<?php

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use StaticAnalysisTester\PsalmTester;
use StaticAnalysisTester\StaticAnalysisTest;

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
