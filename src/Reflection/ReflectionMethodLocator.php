<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative\Reflection;

use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\Iterator;
use ReflectionClass;
use ReflectionMethod;

use function mb_strtolower;

class ReflectionMethodLocator
{
    public function __construct(private readonly Iterator $iterator)
    {
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param non-empty-string $methodName
     */
    public function locate(ReflectionClass $reflectionClass, string $methodName): ?ReflectionMethod
    {
        /** @var string $methodNameLowercase */
        $methodNameLowercase = mb_strtolower($methodName);

        foreach ($this->iterator->generate($reflectionClass) as $reflectionClassCurrent) {
            foreach ($reflectionClassCurrent->getMethods() as $reflectionMethod) {
                if (mb_strtolower($reflectionMethod->getName()) === $methodNameLowercase) {
                    return $reflectionMethod;
                }
            }
        }

        return null;
    }
}
