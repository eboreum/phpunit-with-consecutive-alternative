<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls;

use Closure;
use Eboreum\Caster\Contract\ImmutableObjectInterface;
use Eboreum\PhpunitWithConsecutiveAlternative\Assert;
use Eboreum\PhpunitWithConsecutiveAlternative\Caster;
use Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls;
use Exception;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

use function array_key_exists;
use function array_slice;
use function array_values;
use function array_walk;
use function count;
use function func_get_args;
use function implode;
use function sprintf;
use function str_ends_with;

class CallbackFactory implements ImmutableObjectInterface
{
    /**
     * @param ReflectionClass<object> $reflectionClassParent
     *
     * @return Closure(InvokedCount, ReflectionClass, ReflectionMethod, int, ...MethodCallExpectation|Throwable):mixed
     */
    public function createCallback(
        WillHandleConsecutiveCalls $willHandleConsecutiveCalls,
        InvokedCount $matcher,
        ReflectionClass $reflectionClassParent,
        ReflectionMethod $reflectionMethod,
        int $caseCount,
        MethodCallExpectation|Throwable ...$methodCallExpectations,
    ): Closure {
        /** @var array<int<0,max>, MethodCallExpectation|Throwable> $methodCallExpectationsSequential */
        $methodCallExpectationsSequential = array_values($methodCallExpectations);

        return static function () use (
            $willHandleConsecutiveCalls,
            $matcher,
            $reflectionClassParent,
            $reflectionMethod,
            $caseCount,
            $methodCallExpectationsSequential
        ): mixed {
            $argumentFormatter = $willHandleConsecutiveCalls->getArgumentFormatter();
            $caster = $willHandleConsecutiveCalls->getCaster();

            $invocationIndex = $matcher->numberOfInvocations() - 1;

            Assert::keyExists($methodCallExpectationsSequential, $invocationIndex);

            /** @var MethodCallExpectation|Throwable $case */
            $case = $methodCallExpectationsSequential[$invocationIndex] ?? null;

            if ($case instanceof Throwable) {
                throw $case;
            }

            Assert::isObject($case);
            Assert::isInstanceOf($case, MethodCallExpectation::class);

            /** @var array<int, ReflectionParameter> $reflectionParameters */
            $reflectionParameters = [];

            /** @var int<0,max> $requiredArgumentCount */
            $requiredArgumentCount = 0;

            /** @var bool $hasEncounteredOptionalParameter */
            $hasEncounteredOptionalParameter = false;

            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $reflectionParameters[] = $reflectionParameter;

                if ($reflectionParameter->isOptional()) {
                    $hasEncounteredOptionalParameter = true;
                } elseif (false === $hasEncounteredOptionalParameter) {
                    $requiredArgumentCount++;
                }
            }

            $parameterCount = count($reflectionParameters);
            $actualArguments = func_get_args();
            $actualArgumentsSequential = array_values($actualArguments);
            $expectedArguments = array_values($case->arguments);

            /** @var array<string> $errorMessages */
            $errorMessages = [];

            foreach ($expectedArguments as $index => $expectedValue) {
                if (false === array_key_exists($index, $actualArgumentsSequential)) {
                    $errorMessages[] = sprintf(
                        'Expected argument #%d, but it does not exist',
                        $index + 1,
                    );

                    if ($willHandleConsecutiveCalls->isAbortingOnFirstFailure()) {
                        break;
                    }

                    continue;
                }

                /** @var int|non-empty-string $argumentName */
                $argumentName = $index;

                if (array_key_exists($index, $reflectionParameters)) {
                    $argumentName = $reflectionParameters[$index]->getName();
                }

                /** @var mixed $actualValue */
                $actualValue = $actualArgumentsSequential[$index];

                if ($expectedValue instanceof Callback) {
                    try {
                        $isOkay = $expectedValue->evaluate($actualValue, '', true);
                    } catch (Exception $e) {
                        $errorMessages[] = sprintf(
                            'Argument %s = %s — a "with" callback — threw the exception: %s',
                            $argumentFormatter->formatArgumentName($argumentName),
                            $caster->castTyped($expectedValue),
                            $caster->cast($e),
                        );

                        if ($willHandleConsecutiveCalls->isAbortingOnFirstFailure()) {
                            break;
                        }

                        continue;
                    }

                    if (false === $isOkay) {
                        $errorMessages[] = sprintf(
                            implode('', [
                                'Argument %s = %s — a "with" callback — returned false, meaning the actual',
                                ' value %s is not acceptable',
                            ]),
                            $argumentFormatter->formatArgumentName($argumentName),
                            $caster->castTyped($expectedValue),
                            $caster->castTyped($actualValue),
                        );
                    }

                    if ($willHandleConsecutiveCalls->isAbortingOnFirstFailure()) {
                        break;
                    }

                    continue;
                }

                if ($expectedValue !== $actualValue) {
                    $errorMessages[] = sprintf(
                        'Argument %s = %s was expected to be %s, but it is not',
                        $argumentFormatter->formatArgumentName($argumentName),
                        $caster->castTyped($actualValue),
                        $caster->castTyped($expectedValue),
                    );

                    if ($willHandleConsecutiveCalls->isAbortingOnFirstFailure()) {
                        break;
                    }
                }
            }

            if (
                false === $willHandleConsecutiveCalls->isIgnoringSurplusArguments()
                && (
                    false === $willHandleConsecutiveCalls->isAbortingOnFirstFailure()
                    || ! $errorMessages
                )
            ) {
                $expectedArgumentsCount = count($expectedArguments);

                if (false === $willHandleConsecutiveCalls->isIgnoringArgumentsWithADefaultValue()) {
                    foreach (array_slice($reflectionParameters, $expectedArgumentsCount) as $reflectionParameter) {
                        /** @var ReflectionParameter $reflectionParameter */

                        if (false === $reflectionParameter->isDefaultValueAvailable()) {
                            break;
                        }

                        $expectedArgumentsCount++;
                    }
                }

                if (count($actualArguments) !== $expectedArgumentsCount) {
                    $surplusArguments = array_slice(
                        $actualArguments,
                        $parameterCount,
                        null,
                        true,
                    );

                    if ($surplusArguments) {
                        $surplusArgumentsCount = count($surplusArguments);
                        $argumentRangeEnd = $requiredArgumentCount;
                        $argumentRangeText = (string) $requiredArgumentCount;

                        $errorMessages[] = sprintf(
                            implode('', [
                                'Method was expected to be called with %s %s, but was instead called with %d, with the',
                                ' %d surplus %s being: %s',
                            ]),
                            $argumentRangeText,
                            (1 === $argumentRangeEnd ? 'argument' : 'arguments'),
                            count($actualArguments),
                            $surplusArgumentsCount,
                            1 === $surplusArgumentsCount ? 'argument' : 'arguments',
                            implode(
                                ', ',
                                $argumentFormatter->formatArguments($surplusArguments),
                            ),
                        );
                    }
                }
            }

            if ($errorMessages) {
                $actualArgumentsWithNames = [];

                foreach ($reflectionParameters as $i2 => $reflectionParameter) {
                    $actualArgumentsWithNames[$reflectionParameter->getName()] = $actualArguments[$i2] ?? null;
                }

                $surplus = array_slice($actualArguments, count($reflectionParameters), null, true);

                foreach ($surplus as $i2 => $value) {
                    $actualArgumentsWithNames[$i2] = $value;
                }

                $actualArgumentsWithNamesFormatted = $willHandleConsecutiveCalls
                    ->getArgumentFormatter()
                    ->formatArguments($actualArgumentsWithNames);

                array_walk($errorMessages, static function (string &$v): void {
                    if (false === str_ends_with($v, '.')) {
                        $v .= '.';
                    }
                });

                TestCase::fail(
                    sprintf(
                        "On invocation %d/%d, method call %s->%s(%s) failed because %s %s encountered:\n%s",
                        $invocationIndex + 1,
                        $caseCount,
                        Caster::makeNormalizedClassName($reflectionClassParent),
                        $reflectionMethod->getName(),
                        implode(', ', $actualArgumentsWithNamesFormatted),
                        count($errorMessages),
                        1 === count($errorMessages) ? 'error was' : 'errors were',
                        implode("\n", $errorMessages),
                    ),
                );
            }

            return $case->returnValue;
        };
    }
}
