<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use DateTime;
use Eboreum\Caster\Caster;
use Eboreum\PhpunitWithConsecutiveAlternative\FunctionCallExpectation;
use Eboreum\PhpunitWithConsecutiveAlternative\FunctionCallSequence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(FunctionCallSequence::class)]
class FunctionCallSequenceTest extends TestCase
{
    public function testFormatArgumentsWorks(): void
    {
        $this->assertSame(
            [
                sprintf('{0}: %s', Caster::getInstance()->castTyped(42)),
                sprintf('foo: %s', Caster::getInstance()->castTyped('bar')),
                sprintf('lorem: %s', Caster::getInstance()->castTyped(true)),
            ],
            FunctionCallSequence::formatArguments(
                [
                    0 => 42,
                    'foo' => 'bar',
                    'lorem' => true,
                ],
            ),
        );
    }

    public function testExpectWorks(): void
    {
        $object = $this->createMock(DateTime::class);

        // No 3rd argument. I.e. it'll use the default value.
        $functionCallExpectation1 = new FunctionCallExpectation($object, 1999, 42);

        // Specify 3rd argument.
        $functionCallExpectation2 = new FunctionCallExpectation($object, 2000, 43, 2);

        $functionCallSequence = new FunctionCallSequence();
        $functionCallSequence->expect(
            $object,
            'setISODate',
            $functionCallExpectation1,
            $functionCallExpectation2,
        );

        $this->assertSame($object, $object->setISODate(1999, 42));
        $this->assertSame($object, $object->setISODate(2000, 43, 2));
    }
}
