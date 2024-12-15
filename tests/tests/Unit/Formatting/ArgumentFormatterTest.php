<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative\Formatting;

use Eboreum\PhpunitWithConsecutiveAlternative\Caster;
use Eboreum\PhpunitWithConsecutiveAlternative\Formatting\ArgumentFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(ArgumentFormatter::class)]
class ArgumentFormatterTest extends TestCase
{
    public function testFormatArgumentNameWorks(): void
    {
        $argumentFormatter = new ArgumentFormatter();

        $this->assertSame('{0}', $argumentFormatter->formatArgumentName(0));
        $this->assertSame('{1}', $argumentFormatter->formatArgumentName(1));
        $this->assertSame('{1}', $argumentFormatter->formatArgumentName('1'));
        $this->assertSame('$foo', $argumentFormatter->formatArgumentName('foo'));
        $this->assertSame('$é', $argumentFormatter->formatArgumentName('é'));
        $this->assertSame('$🚀', $argumentFormatter->formatArgumentName('🚀'));
        $this->assertSame('${" 1"}', $argumentFormatter->formatArgumentName(' 1'));
    }

    public function testFormatArgumentsWorks(): void
    {
        $argumentFormatter = new ArgumentFormatter();

        $this->assertSame(
            [
                sprintf('{0} = %s', Caster::getInstance()->castTyped(42)),
                sprintf('$foo = %s', Caster::getInstance()->castTyped('bar')),
                sprintf('$lorem = %s', Caster::getInstance()->castTyped(true)),
                sprintf('$é = %s', Caster::getInstance()->castTyped(null)),
                sprintf('$🚀 = %s', Caster::getInstance()->castTyped(99)),
            ],
            $argumentFormatter->formatArguments(
                [
                    0 => 42,
                    'foo' => 'bar',
                    'lorem' => true,
                    'é' => null,
                    '🚀' => 99,
                ],
            ),
        );
    }
}
