<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\Caster\Caster;
use Eboreum\Exceptional\ExceptionMessageGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function func_get_args;
use function implode;
use function is_int;
use function is_object;
use function json_encode;
use function mb_strtolower;
use function preg_match;
use function sprintf;

/**
 * Credit: https://stackoverflow.com/a/75686291
 */
class FunctionCallSequence
{
    /**
     * @param array<mixed> $arguments
     *
     * @return array<int, string>
     */
    public static function formatArguments(array $arguments): array
    {
        /** @var array<string> $formatted */
        $formatted = [];

        foreach ($arguments as $k => $v) {
            if (is_int($k) || 1 === preg_match('/^\d+$/', $k)) {
                $k = sprintf('{%s}', $k);
            }

            $formatted[] = sprintf('%s: %s', $k, Caster::getInstance()->castTyped($v));
        }

        return $formatted;
    }

    /**
     * @throws RuntimeException
     */
    public function expect(
        MockObject $object,
        string $methodName,
        FunctionCallExpectation ...$functionCallExpectations,
    ): void {
        try {
            /** @var ReflectionClass<object> $reflectionClass */
            $reflectionClass = new ReflectionClass($object);

            /** @var ReflectionClass<object>|null $reflectionClassParent */
            $reflectionClassParent = $reflectionClass->getParentClass() ?: null;

            Assert::isObject($reflectionClassParent);
            assert(is_object($reflectionClassParent));

            /** @var array<string> $errorMessages */
            $errorMessages = [];

            if (empty($functionCallExpectations)) {
                $errorMessages[] = sprintf(
                    'Argument $functionCallExpectations = %s must not be empty',
                    Caster::getInstance()->castTyped($functionCallExpectations),
                );
            }

            if ($errorMessages) {
                throw new RuntimeException(implode('. ', $errorMessages));
            }

            /** @var string $methodNameLowercase */
            $methodNameLowercase = mb_strtolower($methodName);

            /** @var ReflectionMethod|null $reflectionMethod */
            $reflectionMethod = null;

            foreach ($reflectionClassParent->getMethods() as $reflectionMethodIteration) {
                if (mb_strtolower($reflectionMethodIteration->getName()) === $methodNameLowercase) {
                    $reflectionMethod = $reflectionMethodIteration;

                    break;
                }
            }

            if (!$reflectionMethod) {
                throw new RuntimeException(
                    sprintf(
                        'Was unable to locate the method named %s on $object = %s',
                        Caster::getInstance()->cast($methodName),
                        Caster::getInstance()->castTyped($object),
                    ),
                );
            }

            /** @var int $count */
            $count = count($functionCallExpectations);

            $matcher = new InvokedCount($count);

            $object
                ->expects($matcher)
                ->method($methodName)
                ->willReturnCallback(
                    static function () use (
                        $matcher,
                        $reflectionClassParent,
                        $reflectionMethod,
                        $functionCallExpectations,
                        $count
                    ): mixed {
                        $index = $matcher->numberOfInvocations() - 1;

                        Assert::keyExists($functionCallExpectations, $index);

                        /** @var FunctionCallExpectation $case */
                        $case = $functionCallExpectations[$index] ?? null;

                        Assert::isObject($case);
                        Assert::isInstanceOf($case, FunctionCallExpectation::class);

                        $actualArguments = array_values(func_get_args());
                        $expectedArguments = array_values($case->arguments);

                        if (count($actualArguments) > count($expectedArguments)) {
                            // This will happen when arguments have default values.

                            /** @var array<ReflectionParameter> $reflectionParametersToCheckForDefaultValues */
                            $reflectionParametersToCheckForDefaultValues = array_slice(
                                $reflectionMethod->getParameters(),
                                count($expectedArguments),
                            );

                            /** @var array<mixed> $additionalExpectedArguments */
                            $additionalExpectedArguments = [];

                            foreach ($reflectionParametersToCheckForDefaultValues as $reflectionParameter) {
                                if (false === $reflectionParameter->isDefaultValueAvailable()) {
                                    break;
                                }

                                $additionalExpectedArguments[] = $reflectionParameter->getDefaultValue();
                            }

                            if ($additionalExpectedArguments) {
                                $expectedArguments = array_merge($expectedArguments, $additionalExpectedArguments);
                            }
                        }

                        $actualArgumentsFormatted = self::formatArguments($actualArguments);
                        $expectedArgumentsFormatted = self::formatArguments($expectedArguments);

                        /** @var array<int> $argumentIndexesWithDifferences */
                        $argumentIndexesWithDifferences = [];

                        /** @var array<int> $foreach */
                        $foreach = array_unique(
                            array_merge(
                                array_keys($expectedArguments),
                                array_keys($actualArguments),
                            ),
                        );

                        foreach ($foreach as $key) {
                            if (
                                array_key_exists($key, $expectedArguments)
                                && array_key_exists($key, $actualArguments)
                            ) {
                                if ($expectedArguments[$key] !== $actualArguments[$key]) {
                                    $argumentIndexesWithDifferences[] = $key;
                                }
                            } else {
                                $argumentIndexesWithDifferences[] = $key;
                            }
                        }

                        TestCase::assertSame(
                            0,
                            count($argumentIndexesWithDifferences),
                            sprintf(
                                implode('', [
                                    'Method call %s->%s(%s), on invocation %d/%d, has %d %s and was expected the have',
                                    ' the following %d %s, ...->%s(%s), but the values on the following %d parameter',
                                    ' %s different: %s',
                                ]),
                                Caster::makeNormalizedClassName($reflectionClassParent),
                                $reflectionMethod->getName(),
                                implode(', ', $actualArgumentsFormatted),
                                $index + 1,
                                $count,
                                count($actualArgumentsFormatted),
                                (1 === count($actualArgumentsFormatted) ? 'argument' : 'arguments'),
                                count($expectedArgumentsFormatted),
                                (1 === count($expectedArgumentsFormatted) ? 'argument' : 'arguments'),
                                $reflectionMethod->getName(),
                                implode(', ', $expectedArgumentsFormatted),
                                count($argumentIndexesWithDifferences),
                                (1 === count($argumentIndexesWithDifferences) ? 'index is' : 'indexes are'),
                                json_encode($argumentIndexesWithDifferences),
                            ),
                        );

                        return $case->returnValue;
                    },
                );
        } catch (Throwable $t) {
            throw new RuntimeException(
                ExceptionMessageGenerator::getInstance()->makeFailureInMethodMessage(
                    $this,
                    new ReflectionMethod($this, __FUNCTION__),
                    func_get_args(),
                ),
                0,
                $t,
            );
        }
    }
}
