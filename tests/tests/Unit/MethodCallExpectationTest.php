<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\PhpunitWithConsecutiveAlternative\MethodCallExpectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MethodCallExpectation::class)]
class MethodCallExpectationTest extends TestCase
{
    public function testBasics(): void
    {
        $methodCallExpectation = new MethodCallExpectation(1, 2, 3);

        $this->assertSame(1, $methodCallExpectation->returnValue);
        $this->assertSame([2, 3], $methodCallExpectation->arguments);
    }
}
