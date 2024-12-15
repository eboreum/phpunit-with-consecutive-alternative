A sensible alternative to PHPUnit's now removed "withConsecutive" method
===============================
Do you miss the old "[withConsecutive](https://docs.phpunit.de/en/8.5/test-doubles.html?highlight=withconsecutive#test-doubles-mock-objects-examples-with-consecutive-php)" method in PHPUnit? This library solves that problem for you.

# Using this library

Within a test method — i.e. a method inside a child of `\PHPUnit\Framework\TestCase` — simply do the following:

```php
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

**Notice:** We do not hook into `\PHPUnit\Framework\MockObject\MockObject` and so — contrary to the original `withConsecutive` — we place this on `\PHPUnit\Framework\TestCase` instead.

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