A sensible alternative to PHPUnit's now removed "withConsecutive" method
===============================
Do you miss the old "withConsecutive" method in PHPUnit? This library fixes that problem for you.

Within an instance of `\PHPUnit\Framework\TestCase`, simply do the following:

```php
use Eboreum\PhpunitWithConsecutiveAlternative\FunctionCallSequence;

...

$functionCallSequence = new FunctionCallSequence();
$functionCallSequence->expect(
    $this->createMock(DateTime::class),
    'setISODate',
    new FunctionCallExpectation($object, 1999, 42),
    new FunctionCallExpectation($object, 2000, 43, 2),
);

...


$this->assertSame($object, $object->setISODate(1999, 42));
$this->assertSame($object, $object->setISODate(2000, 43, 2));
```

**Why was "withConsecutive" removed?**

You can see some of the reasoning and discussion about it here: https://github.com/sebastianbergmann/phpunit/issues/4026

No convenient alternative has been provided.

One of the core problems was the disjointed connection between arguments and their corresponding return values, as they would be provided in separate method, i.e.:

 - `withConsecutive`
 - `willReturnOnConsecutiveCalls` or `willReturn`

In order to make this connection abundantly clear, the value-object class `\Eboreum\PhpunitWithConsecutiveAlternative\FunctionCallExpectation` has been implemented. Within it is stored a return value (required) and 0 or more arguments.