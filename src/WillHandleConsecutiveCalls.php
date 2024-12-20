<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\Caster\Contract\CasterInterface;
use Eboreum\Caster\Contract\ImmutableObjectInterface;
use Eboreum\PhpunitWithConsecutiveAlternative\Formatting\ArgumentFormatter;
use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\Iterator;
use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionMethodLocator;
use Eboreum\PhpunitWithConsecutiveAlternative\WillHandleConsecutiveCalls\CallbackFactory;
use PHPUnit\Framework\Exception as PHPUnitException;
use PHPUnit\Framework\MockObject\ConfigurableMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\MockObject\StubInternal;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function assert;
use function count;
use function implode;
use function is_object;
use function sprintf;

class WillHandleConsecutiveCalls implements ImmutableObjectInterface
{
    private CasterInterface $caster;

    private ArgumentFormatter $argumentFormatter;

    private ReflectionMethodLocator $reflectionMethodLocator;

    private CallbackFactory $callbackFactory;

    /**
     * When true, as soon as any argument is invalid, processing of any additional arguments will NOT occur. Only the
     * error for that single argument is appear.
     *
     * When false, all arguments will be processed and all errors on all arguments will be reported. This can get
     * spammy.
     */
    private bool $isAbortingOnFirstFailure = true;

    /**
     * When an argument has a default value, and said argument is not provided in a method call expectation, it may
     * either get ignored or cause an error.
     *
     * When true, arguments with default values will be ignored and will NOT cause an error.
     *
     * When false, arguments with default values, which do not exist in the method call expectation, will cause an
     * error.
     */
    private bool $isIgnoringArgumentsWithADefaultValue = false;

    /**
     * Surplus arguments are arguments being pass, which do not have a dedicated parameter. I.e. a method call is being
     * overloaded and typically functions like `func_get_arg` or `func_get_args` are involved in the method body.
     *
     * When true, surplus arguments will be ignored. I.e. these will NOT cause an error.
     *
     * When false, if a method call receives additional arguments, other than those that have been provided by the
     * actual method call, it will register as an error.
     */
    private bool $isIgnoringSurplusArguments = false;

    public function __construct(
        ?CasterInterface $caster = null,
        ?ArgumentFormatter $argumentFormatter = null,
        ?ReflectionMethodLocator $reflectionMethodLocator = null,
        ?CallbackFactory $callbackFactory = null,
    ) {
        $this->caster = $caster ?? Caster::getInstance();
        $this->argumentFormatter = $argumentFormatter ?? new ArgumentFormatter();
        $this->reflectionMethodLocator = $reflectionMethodLocator ?? new ReflectionMethodLocator(new Iterator());
        $this->callbackFactory = $callbackFactory ?? new CallbackFactory();
    }

    /**
     * @param non-empty-string $methodName
     *
     * @throws PHPUnitException
     * @throws RuntimeException
     */
    public function expectConsecutiveCalls(
        MockObject $object,
        string $methodName,
        MethodCallExpectation|Throwable ...$methodCallExpectations,
    ): void {
        try {
            /** @var array<string> $errorMessages */
            $errorMessages = [];

            /** @var ReflectionClass<object> $reflectionClass */
            $reflectionClass = new ReflectionClass($object);

            if (empty($methodCallExpectations)) {
                $errorMessages[] = sprintf(
                    'Argument $methodCallExpectations = %s must not be empty',
                    $this->caster->castTyped($methodCallExpectations),
                );
            }

            /** @var ReflectionMethod|null $reflectionMethod */
            $reflectionMethod = null;

            if ($object instanceof StubInternal) {
                foreach ($object->__phpunit_state()->configurableMethods() as $configurableMethod) {
                    /** @var ConfigurableMethod $configurableMethod */

                    if ($configurableMethod->name() === $methodName) {
                        $iterator = new Iterator();

                        foreach ($iterator->generate($reflectionClass) as $reflectionClassCurrent) {
                            if ($reflectionClassCurrent->hasMethod($methodName)) {
                                $reflectionMethod = $reflectionClassCurrent->getMethod($methodName);

                                break;
                            }
                        }
                    }

                    if ($reflectionMethod) {
                        break;
                    }
                }
            } else {
                /** @var ReflectionMethod|null $reflectionMethod */
                $reflectionMethod = $this->reflectionMethodLocator->locate($reflectionClass, $methodName);
            }

            if (!$reflectionMethod) {
                $errorMessages[] = sprintf(
                    'Method named %s does not exist on $object = %s',
                    $this->caster->cast($methodName),
                    $this->caster->castTyped($object),
                );
            }

            if ($errorMessages) {
                throw new PHPUnitException(implode('. ', $errorMessages));
            }

            assert(is_object($reflectionMethod));

            /** @var int<0,max> $caseCount */
            $caseCount = count($methodCallExpectations);

            $matcher = new InvokedCount($caseCount);

            $willReturnCallback = $this->callbackFactory->createCallback(
                $this,
                $matcher,
                $reflectionClass,
                $reflectionMethod,
                $caseCount,
                ...$methodCallExpectations,
            );

            $object
                ->expects($matcher)
                ->method($methodName)
                ->willReturnCallback($willReturnCallback);
        } catch (PHPUnitException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new RuntimeException(
                sprintf(
                    implode('', [
                        'Failure in \\%s->expectConsecutiveCalls(',
                            '$object = %s',
                            ', $methodName = %s',
                            ', $methodCallExpectations = (array(%d)) ...%s',
                        ') inside %s',
                    ]),
                    self::class,
                    Caster::getInstance()->castTyped($object),
                    Caster::getInstance()->castTyped($methodName),
                    count($methodCallExpectations),
                    Caster::getInstance()->cast($methodCallExpectations),
                    Caster::getInstance()->castTyped($this),
                ),
                0,
                $t,
            );
        }
    }

    public function getArgumentFormatter(): ArgumentFormatter
    {
        return $this->argumentFormatter;
    }

    public function getCallbackFactory(): CallbackFactory
    {
        return $this->callbackFactory;
    }

    public function getCaster(): CasterInterface
    {
        return $this->caster;
    }

    public function getReflectionMethodLocator(): ReflectionMethodLocator
    {
        return $this->reflectionMethodLocator;
    }

    public function isAbortingOnFirstFailure(): bool
    {
        return $this->isAbortingOnFirstFailure;
    }

    public function isIgnoringArgumentsWithADefaultValue(): bool
    {
        return $this->isIgnoringArgumentsWithADefaultValue;
    }

    public function isIgnoringSurplusArguments(): bool
    {
        return $this->isIgnoringSurplusArguments;
    }

    /**
     * Returns a clone.
     */
    public function withIsAbortingOnFirstFailure(bool $isAbortingOnFirstFailure): self
    {
        $clone = clone $this;
        $clone->isAbortingOnFirstFailure = $isAbortingOnFirstFailure;

        return $clone;
    }

    /**
     * Returns a clone.
     */
    public function withIsIgnoringArgumentsWithADefaultValue(bool $isIgnoringArgumentsWithADefaultValue): self
    {
        $clone = clone $this;
        $clone->isIgnoringArgumentsWithADefaultValue = $isIgnoringArgumentsWithADefaultValue;

        return $clone;
    }

    /**
     * Returns a clone.
     */
    public function withIsIgnoringSurplusArguments(bool $isIgnoringSurplusArguments): self
    {
        $clone = clone $this;
        $clone->isIgnoringSurplusArguments = $isIgnoringSurplusArguments;

        return $clone;
    }
}
