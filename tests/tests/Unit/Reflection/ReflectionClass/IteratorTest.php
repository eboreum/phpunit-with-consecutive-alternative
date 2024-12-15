<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass;

use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TestResource\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\IteratorTest\testGenerateWorks\ClassA; // phpcs:ignore
use TestResource\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\IteratorTest\testGenerateWorks\ClassB; // phpcs:ignore
use TestResource\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\IteratorTest\testGenerateWorks\TraitA; // phpcs:ignore
use TestResource\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\IteratorTest\testGenerateWorks\TraitB; // phpcs:ignore

use function array_shift;

#[CoversClass(Iterator::class)]
class IteratorTest extends TestCase
{
    public function testGenerateWorks(): void
    {
        $iterator = new Iterator();

        $expectedClassNames = [
            ClassB::class,
            TraitB::class,
            ClassA::class,
            TraitA::class,
        ];

        foreach ($iterator->generate(new ReflectionClass(ClassB::class)) as $reflectionClassCurrent) {
            $expectedClassName = array_shift($expectedClassNames);

            $this->assertSame($expectedClassName, $reflectionClassCurrent->getName());
        }
    }
}
