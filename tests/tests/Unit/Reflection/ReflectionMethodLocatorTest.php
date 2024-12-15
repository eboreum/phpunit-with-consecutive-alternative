<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Reflection;

use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionClass\Iterator;
use Eboreum\PhpunitWithConsecutiveAlternative\Reflection\ReflectionMethodLocator;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function assert;
use function is_object;

#[CoversClass(ReflectionMethodLocator::class)]
class ReflectionMethodLocatorTest extends TestCase
{
    public function testLocateWorks(): void
    {
        $iterator = $this->createMock(Iterator::class);

        $reflectionMethodLocator = new ReflectionMethodLocator($iterator);

        $reflectionClass = new ReflectionClass(self::class);

        $generator = (static function () use ($reflectionClass): Generator {
            yield $reflectionClass;

            return null;
        })();

        $iterator
            ->expects($this->exactly(3))
            ->method('generate')
            ->with($reflectionClass)
            ->willReturn($generator);

        $reflectionMethod = $reflectionMethodLocator->locate($reflectionClass, __FUNCTION__);

        $this->assertIsObject($reflectionMethod);
        assert(is_object($reflectionMethod));
        $this->assertSame(__FUNCTION__, $reflectionMethod->getName());
        $this->assertSame(self::class, $reflectionMethod->getDeclaringClass()->getName());

        $reflectionMethod = $reflectionMethodLocator->locate($reflectionClass, 'setUp');

        $this->assertIsObject($reflectionMethod);
        assert(is_object($reflectionMethod));
        $this->assertSame('setUp', $reflectionMethod->getName());
        $this->assertSame(TestCase::class, $reflectionMethod->getDeclaringClass()->getName());

        $this->assertNull($reflectionMethodLocator->locate($reflectionClass, 'foo'));
    }
}
