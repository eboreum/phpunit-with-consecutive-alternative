<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use DateTime;
use Eboreum\Caster\Contract\CasterInterface;
use Eboreum\PhpunitWithConsecutiveAlternative\Caster;
use Eboreum\PhpunitWithConsecutiveAlternative\Formatting\ArgumentFormatter;
use Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation;
use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionMethodLocator;
use Eboreum\PhpunitWithConsecutiveAlternative\RuntimeException;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls\CallbackFactory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;

use function implode;
use function sprintf;

#[CoversClass(WillHandleConsecutiveCalls::class)]
#[CoversClass(CallbackFactory::class)]
class WillHandleConsecutiveCallsTest extends TestCase
{
    public function testBasics(): void
    {
        $argumentFormatter = $this->createMock(ArgumentFormatter::class);
        $callbackFactory = $this->createMock(CallbackFactory::class);
        $caster = $this->createMock(CasterInterface::class);
        $reflectionMethodLocator = $this->createMock(ReflectionMethodLocator::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(
            $caster,
            $argumentFormatter,
            $reflectionMethodLocator,
            $callbackFactory,
        );

        $this->assertSame($argumentFormatter, $willHandleConsecutiveCalls->getArgumentFormatter());
        $this->assertSame($callbackFactory, $willHandleConsecutiveCalls->getCallbackFactory());
        $this->assertSame($caster, $willHandleConsecutiveCalls->getCaster());
        $this->assertSame($reflectionMethodLocator, $willHandleConsecutiveCalls->getReflectionMethodLocator());
    }

    public function testExpectConsecutiveCallsHandlesExceptionGracefully(): void
    {
        $object = $this->createMock(DateTime::class);
        $reflectionMethodLocator = $this->createMock(ReflectionMethodLocator::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(
            caster: Caster::getInstance(),
            reflectionMethodLocator: $reflectionMethodLocator,
        );

        $exception = new \Exception();

        $reflectionMethodLocator
            ->expects($this->once())
            ->method('locate')
            ->willThrowException($exception);

        $methodCallExpectation = new MethodCallExpectation(null);

        try {
            $willHandleConsecutiveCalls->expectConsecutiveCalls($object, 'setTimestamp', $methodCallExpectation);
        } catch (Throwable $t) {
            $currentThrowable = $t;
            $this->assertSame(RuntimeException::class, $currentThrowable::class);
            $this->assertSame(
                sprintf(
                    implode('', [
                        'Failure in \\%s->expectConsecutiveCalls(',
                            '$object = %s',
                            ', $methodName = %s',
                            ', $methodCallExpectations = (array(1)) ...%s',
                        ') inside %s',
                    ]),
                    WillHandleConsecutiveCalls::class,
                    Caster::getInstance()->castTyped($object),
                    Caster::getInstance()->castTyped('setTimestamp'),
                    Caster::getInstance()->cast([$methodCallExpectation]),
                    Caster::getInstance()->castTyped($willHandleConsecutiveCalls),
                ),
                $currentThrowable->getMessage(),
            );

            $currentThrowable = $currentThrowable->getPrevious();
            $this->assertSame($exception, $currentThrowable);

            return;
        }

        $this->fail('Exception was never thrown.');
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenArgumentMethodCallExpectationsIsEmpty(): void
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $methodCallExpectations = %s must not be empty',
                Caster::getInstance()->castTyped([]),
            ),
        );

        $willHandleConsecutiveCalls->expectConsecutiveCalls($object, 'setISODate');
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenArgumentMethodNamePointsToANonexistentMethod(): void
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            sprintf(
                'Method named %s does not exist on $object = %s',
                Caster::getInstance()->cast('foo45e4ef20869244c4887cc31cdc5df254'),
                Caster::getInstance()->castTyped($object),
            ),
        );

        $willHandleConsecutiveCalls->expectConsecutiveCalls(
            $object,
            'foo45e4ef20869244c4887cc31cdc5df254',
            new MethodCallExpectation($object),
        );
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToAMissingArgumentForBreakCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(true);

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls($object, 'setTimestamp', new MethodCallExpectation($object, 0, 1));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call %s->setTimestamp($timestamp = %s) failed because 1 error was',
                    ' encountered:',
                    "\n",
                    'Expected argument #2, but it does not exist.',
                ]),
                Caster::makeNormalizedClassName(new ReflectionClass(DateTime::class)),
                Caster::getInstance()->castTyped(0),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToAMissingArgumentForContinueCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(false);

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls($object, 'setTimestamp', new MethodCallExpectation($object, 0, 1));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call %s->setTimestamp($timestamp = %s) failed because 1 error was',
                    ' encountered:',
                    "\n",
                    'Expected argument #2, but it does not exist.',
                ]),
                Caster::makeNormalizedClassName(new ReflectionClass(DateTime::class)),
                Caster::getInstance()->castTyped(0),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToACallbackReturningFalseForBreakCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(true);

        $callback = TestCase::callback(
            static function (): bool {
                return false;
            },
        );

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, $callback),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'Argument $timestamp = %s — a "with" callback — returned false, meaning the actual',
                    ' value %s is not acceptable',
                ]),
                Caster::getInstance()->castTyped($callback),
                Caster::getInstance()->castTyped(0),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToACallbackReturningFalseForContinueCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(false);

        $callback = TestCase::callback(
            static function (): bool {
                return false;
            },
        );

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, $callback),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'Argument $timestamp = %s — a "with" callback — returned false, meaning the actual',
                    ' value %s is not acceptable',
                ]),
                Caster::getInstance()->castTyped($callback),
                Caster::getInstance()->castTyped(0),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToAnExceptionWithinTheCallbackForBreakCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(true);

        $exception = new \RuntimeException('foo');

        $callback = TestCase::callback(
            static function () use ($exception): bool {
                throw $exception;
            },
        );

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, $callback),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call \\%s->setTimestamp($timestamp = %s) failed because 1 error was',
                    ' encountered:',
                    "\n",
                    'Argument $timestamp = %s — a "with" callback — threw the exception: %s',
                ]),
                DateTime::class,
                Caster::getInstance()->castTyped(0),
                Caster::getInstance()->castTyped($callback),
                Caster::getInstance()->cast($exception),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToAnExceptionWithinTheCallbackForContinueCase(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(false);

        $exception = new \RuntimeException('foo');

        $callback = TestCase::callback(
            static function () use ($exception): bool {
                throw $exception;
            },
        );

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, $callback),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call \\%s->setTimestamp($timestamp = %s) failed because 1 error was',
                    ' encountered:',
                    "\n",
                    'Argument $timestamp = %s — a "with" callback — threw the exception: %s.',
                ]),
                DateTime::class,
                Caster::getInstance()->castTyped(0),
                Caster::getInstance()->castTyped($callback),
                Caster::getInstance()->cast($exception),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationFailsDueToExpectedValueNotMatchingActualValue(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls->withIsAbortingOnFirstFailure(true);

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, 1),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call \\%s->setTimestamp($timestamp = %s) failed because 1 error was',
                    ' encountered:',
                    "\n",
                    'Argument $timestamp = %s was expected to be %s, but it is not.',
                ]),
                DateTime::class,
                Caster::getInstance()->castTyped(0),
                Caster::getInstance()->castTyped(0),
                Caster::getInstance()->castTyped(1),
            ),
        );

        $object->setTimestamp(0);
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationHasSurplusArguments(): void
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls
            ->withIsAbortingOnFirstFailure(true)
            ->withIsIgnoringSurplusArguments(false)
            ->withIsIgnoringArgumentsWithADefaultValue(false);

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setTimestamp',
                new MethodCallExpectation($object, 0),
            );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call \\%s->setTimestamp($timestamp = %s, {1} = %s) failed because 1',
                    ' error was encountered:',
                    "\n",
                    'Method was expected to be called with 1 argument, but was instead called with 2, with the 1',
                    ' surplus argument being: {1} = %s.',
                ]),
                DateTime::class,
                Caster::getInstance()->castTyped(0),
                Caster::getInstance()->castTyped(2),
                Caster::getInstance()->castTyped(2),
            ),
        );

        $object->setTimestamp(0, 2); // @phpstan-ignore-line
    }

    public function testExpectConsecutiveCallsThrowsExceptionWhenInvocationHasLessExpectedArgumentsThanActualArguments(): void // phpcs:ignore
    {
        $object = $this->createMock(DateTime::class);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls(caster: Caster::getInstance());
        $willHandleConsecutiveCalls = $willHandleConsecutiveCalls
            ->withIsAbortingOnFirstFailure(true)
            ->withIsIgnoringSurplusArguments(false)
            ->withIsIgnoringArgumentsWithADefaultValue(false);

        $willHandleConsecutiveCalls
            ->expectConsecutiveCalls(
                $object,
                'setDate',
                new MethodCallExpectation($object, 2000),
            );


        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            sprintf(
                implode('', [
                    'On invocation 1/1, method call \\%s->setDate($year = %s, $month = %s, $day = %s, {3} = %s) failed',
                    ' because 1 error was encountered:',
                    "\n",
                    'Method was expected to be called with 3 arguments, but was instead called with 4, with the 1',
                    ' surplus argument being: {3} = %s.',
                ]),
                DateTime::class,
                Caster::getInstance()->castTyped(2000),
                Caster::getInstance()->castTyped(1),
                Caster::getInstance()->castTyped(2),
                Caster::getInstance()->castTyped(3),
                Caster::getInstance()->castTyped(3),
            ),
        );

        $object->setDate(2000, 1, 2, 3); // @phpstan-ignore-line We test for this, specifically.
    }

    public function testExpectConsecutiveCallsWorksWhenExactNumberOfArgumentsAreProvided(): void
    {
        $object = $this->createMock(DateTime::class);

        // No 3rd argument. I.e. it'll use the default value.
        $methodCallExpectation1 = new MethodCallExpectation($object, 1999, 42);

        // Specify 3rd (surplus) argument.
        $methodCallExpectation2 = new MethodCallExpectation($object, 2000, 43, 2);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls();

        $willHandleConsecutiveCalls->expectConsecutiveCalls(
            $object,
            'setISODate',
            $methodCallExpectation1,
            $methodCallExpectation2,
        );

        $this->assertSame($object, $object->setISODate(1999, 42));
        $this->assertSame($object, $object->setISODate(2000, 43, 2));
    }

    public function testExpectConsecutiveCallsWorksWithSurplusArgumentsButOnlyWhenTheyAreExpected(): void
    {
        $object = $this->createMock(DateTime::class);

        $methodCallExpectation = new MethodCallExpectation($object, 2000, 42, 2, 99);

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls();

        $willHandleConsecutiveCalls->expectConsecutiveCalls(
            $object,
            'setISODate',
            $methodCallExpectation,
        );

        $this->assertSame(
            $object,
            $object->setISODate(2000, 42, 2, 99), // @phpstan-ignore-line We test for this, specifically.
        );
    }

    public function testExpectConsecutiveCallsWorksWhenAThrowableIsExpectedToBeThrown(): void
    {
        $object = $this->createMock(DateTime::class);

        $exception = new \Exception();

        $willHandleConsecutiveCalls = new WillHandleConsecutiveCalls();

        $willHandleConsecutiveCalls->expectConsecutiveCalls(
            $object,
            'setISODate',
            $exception,
        );

        try {
            $object->setISODate(2000, 42, 2);
        } catch (Throwable $t) {
            $this->assertSame($exception, $t);

            return;
        }

        $this->fail('Exception was never thrown.');
    }

    public function testWithIsAbortingOnFirstFailureWorks(): void
    {
        $willHandleConsecutiveCallsA = new WillHandleConsecutiveCalls();
        $willHandleConsecutiveCallsB = $willHandleConsecutiveCallsA->withIsAbortingOnFirstFailure(false);

        $this->assertNotSame($willHandleConsecutiveCallsA, $willHandleConsecutiveCallsB);
        $this->assertTrue($willHandleConsecutiveCallsA->isAbortingOnFirstFailure());
        $this->assertFalse($willHandleConsecutiveCallsB->isAbortingOnFirstFailure());
    }

    public function testWithIsIgnoringArgumentsWithADefaultValueWorks(): void
    {
        $willHandleConsecutiveCallsA = new WillHandleConsecutiveCalls();
        $willHandleConsecutiveCallsB = $willHandleConsecutiveCallsA->withIsIgnoringArgumentsWithADefaultValue(true);

        $this->assertNotSame($willHandleConsecutiveCallsA, $willHandleConsecutiveCallsB);
        $this->assertFalse($willHandleConsecutiveCallsA->isIgnoringArgumentsWithADefaultValue());
        $this->assertTrue($willHandleConsecutiveCallsB->isIgnoringArgumentsWithADefaultValue());
    }

    public function testWithIsIgnoringSurplusArgumentsWorks(): void
    {
        $willHandleConsecutiveCallsA = new WillHandleConsecutiveCalls();
        $willHandleConsecutiveCallsB = $willHandleConsecutiveCallsA->withIsIgnoringSurplusArguments(true);

        $this->assertNotSame($willHandleConsecutiveCallsA, $willHandleConsecutiveCallsB);
        $this->assertFalse($willHandleConsecutiveCallsA->isIgnoringSurplusArguments());
        $this->assertTrue($willHandleConsecutiveCallsB->isIgnoringSurplusArguments());
    }
}
