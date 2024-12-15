A sensible alternative to PHPUnit's now removed "withConsecutive" method
===============================

![license](https://img.shields.io/github/license/eboreum/phpunit-with-consecutive-alternative?label=license)
![build](https://github.com/eboreum/phpunit-with-consecutive-alternative/actions/workflows/build.yml/badge.svg?branch=main)
[![Code Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/kafoso/65fafc1ce636292d66a42dce4b4b751a/raw/test-coverage__main.json)](https://github.com/eboreum/phpunit-with-consecutive-alternative/actions)
[![PHPStan Level](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/kafoso/65fafc1ce636292d66a42dce4b4b751a/raw/phpstan-level__main.json)](https://github.com/eboreum/phpunit-with-consecutive-alternative/actions)

Do you miss the old "[withConsecutive](https://docs.phpunit.de/en/8.5/test-doubles.html?highlight=withconsecutive#test-doubles-mock-objects-examples-with-consecutive-php)" method in PHPUnit? This library solves that problem for you.

# Using this library

Within a test method — i.e. a method inside a child of `\PHPUnit\Framework\TestCase` — simply do the following:

```php
use Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls;

...

$object = $this->createMock(DateTime::class);

$willHandleConsecutiveCalls = new WillHandleConsecutiveCalls();
$willHandleConsecutiveCalls->expectConsecutiveCalls(
    $object,
    'setISODate',
    new MethodCallExpectation($object, 1999, 42),
    new MethodCallExpectation($object, 2000, 43, 2),
);

...


$this->assertSame($object, $object->setISODate(1999, 42));
$this->assertSame($object, $object->setISODate(2000, 43, 2));
```

# Make it easy for yourself

Make your own (abstract) test case class and simply add a proxy method doing the above.

Example:

```php
use Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractUnitTestCase extends TestCase
{
    /**
     * @param non-empty-string $methodName
     */
    final public static function expectConsecutiveCalls(
        MockObject $object,
        string $methodName,
        MethodCallExpectation ...$methodCallExpectations,
    ): void {
        (new WillHandleConsecutiveCalls())->expectConsecutiveCalls($object, $methodName, ...$methodCallExpectations);
    }
}
```

⚠️ **Notice:** We do _**not**_ hook into `\PHPUnit\Framework\MockObject\Builder\InvocationMocker` and so — contrary to the original `withConsecutive` — we place the above method on `\PHPUnit\Framework\TestCase` instead.

 - In simpler terms: You call a method on `\PHPUnit\Framework\TestCase`, with the mock as an argument, rather than calling a method on the mock itself.
 - This change is largely because PHPUnit utilizes the "final" keyword a lot on classes and does not support decorators, making extending (an instance of) InvocationMocker nigh-impossible (unless we do evil class-override things).

# Why was "withConsecutive" removed?

You can see some of the reasoning and discussion about it here: https://github.com/sebastianbergmann/phpunit/issues/4026

The main reason [@sebastianbergmann](https://github.com/sebastianbergmann) decided to remove `withConsecutive` (and `at`, by the way) was with the argument: Don't mock what you don't own. Sadly, the resource he links to in https://github.com/sebastianbergmann/phpunit/issues/4026 — being https://thephp.cc/news/2021/04/do-not-mock-what-you-do-not-own — is now a 404 page. Sebastian is a fantastic person and I and many others greatly appreciate his work over the years. Truly! However, I for one **disagree** with the "Don't mock what you don't own" sentiment. If something is part of the public API — third-part or not — one should be able to and allowed to mock it. Period.

No convenient alternative has been provided in PHPUnit itself.

# A core problem — fixed

A core problem was the disjointed connection between arguments and their corresponding return values, as they would be provided in separate methods, i.e.:

 - `withConsecutive`
 - `willReturnOnConsecutiveCalls` or `willReturn`

In order to make this connection abundantly clear, the value-object class `\Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation` has been implemented. Within it is stored a return value (required) and 0 or more arguments.

**Notice:** You may indeed use callbacks (i.e. `\PHPUnit\Framework\TestCase->callback(...)`) for the arguments instead of the actual values.

# License & Disclaimer

See [`LICENSE`](LICENSE) file. Basically: Use this library at your own risk.

# Contributing

We prefer that you create a ticket and or a pull request at https://github.com/eboreum/phpunit-with-consecutive-alternative, and have a discussion about a feature or bug here.

# Credits

## Authors

%composer.json.authors%
