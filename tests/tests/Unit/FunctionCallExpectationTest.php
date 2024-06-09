<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\PhpunitWithConsecutiveAlternative\FunctionCallExpectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionCallExpectation::class)]
class FunctionCallExpectationTest extends TestCase
{
    public function testBasics(): void
    {
        $functionCallExpectation = new FunctionCallExpectation(1, 2, 3);

        $this->assertSame(1, $functionCallExpectation->returnValue);
        $this->assertSame([2, 3], $functionCallExpectation->arguments);
    }
}
