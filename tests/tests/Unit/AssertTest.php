<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use DateTime;
use Eboreum\PhpunitWithConsecutiveAlternative\Assert;
use Eboreum\PhpunitWithConsecutiveAlternative\Caster;
use Eboreum\PhpunitWithConsecutiveAlternative\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(Assert::class)]
class AssertTest extends TestCase
{
    public function testClassExistsThrowsExceptionWhenClassDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $className = %s references a class, which does not exist',
                Caster::getInstance()->castTyped('42ee3757-d150-40dd-a49b-b683a64cc08c'),
            ),
        );

        Assert::classExists('42ee3757-d150-40dd-a49b-b683a64cc08c');
    }

    public function testClassExistsWorks(): void
    {
        Assert::classExists(DateTime::class);

        $this->assertTrue(true);
    }

    public function testIsObjectThrowsExceptionWhenClassDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $value = %s must be an object, but it is not',
                Caster::getInstance()->castTyped(42),
            ),
        );

        Assert::isObject(42);
    }

    public function testIsObjectWorks(): void
    {
        $object = (object) [];

        Assert::isObject($object);

        $this->assertTrue(true);
    }

    public function testIsInstanceOfThrowsExceptionWhenClassDoesNotExist(): void
    {
        $object = $this->createMock(DateTime::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $object = %s must an instance of \\stdClass, but it is not',
                Caster::getInstance()->castTyped($object),
            ),
        );

        Assert::isInstanceOf($object, 'stdClass');
    }

    public function testIsInstanceOfWorks(): void
    {
        $object = $this->createMock(DateTime::class);

        Assert::isInstanceOf($object, DateTime::class);

        $this->assertTrue(true);
    }

    public function testKeyExistsThrowsExceptionWhenClassDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $array = %s does not have a key "foo"',
                Caster::getInstance()->castTyped([]),
            ),
        );

        Assert::keyExists([], 'foo');
    }

    public function testKeyExistsWorks(): void
    {
        Assert::keyExists([0, 'foo' => 1], 'foo');

        $this->assertTrue(true);
    }
}
