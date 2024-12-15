<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass;

use Generator;
use ReflectionClass;

use function array_merge;
use function array_shift;
use function array_values;

class Iterator
{
    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return Generator<ReflectionClass<object>>:null
     */
    public function generate(ReflectionClass $reflectionClass): Generator
    {
        /** @var array<ReflectionClass<object>> $reflectionClassStack */
        $reflectionClassStack = [$reflectionClass];

        while ($reflectionClassStack) {
            /** @var ReflectionClass<object> $reflectionClassCurrent */
            $reflectionClassCurrent = array_shift($reflectionClassStack);

            yield $reflectionClassCurrent;

            $traits = $reflectionClassCurrent->getTraits();

            if ($traits) {
                // Prepend, because they must be processed before a potential parent class.
                $reflectionClassStack = array_merge(array_values($traits), $reflectionClassStack);
            }

            $parent = $reflectionClassCurrent->getParentClass();

            if ($parent) {
                $reflectionClassStack[] = $parent;
            }
        }

        return null;
    }
}
