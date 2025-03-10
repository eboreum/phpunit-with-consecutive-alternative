<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

use function array_key_exists;
use function class_exists;
use function is_a;
use function is_object;
use function sprintf;

abstract class Assert
{
    /**
     * @throws AssertException
     */
    public static function classExists(string $className): void
    {
        if (class_exists($className)) {
            return;
        }

        throw new AssertException(
            sprintf(
                'Argument $className = %s references a class, which does not exist',
                Caster::getInstance()->castTyped($className),
            ),
        );
    }

    /**
     * @throws AssertException
     */
    public static function isObject(mixed $value): void
    {
        if (is_object($value)) {
            return;
        }

        throw new AssertException(
            sprintf(
                'Argument $value = %s must be an object, but it is not',
                Caster::getInstance()->castTyped($value),
            ),
        );
    }

    /**
     * @param class-string $className
     *
     * @throws AssertException
     */
    public static function isInstanceOf(object $object, string $className): void
    {
        self::classExists($className);

        if (is_a($object, $className)) {
            return;
        }

        throw new AssertException(
            sprintf(
                'Argument $object = %s must an instance of \\%s, but it is not',
                Caster::getInstance()->castTyped($object),
                $className,
            ),
        );
    }

    /**
     * @param array<mixed> $array
     *
     * @throws AssertException
     */
    public static function keyExists(array $array, int|string $key): void
    {
        if (array_key_exists($key, $array)) {
            return;
        }

        throw new AssertException(
            sprintf(
                'Argument $array = %s does not have a key %s',
                Caster::getInstance()->castTyped($array),
                Caster::getInstance()->cast($key),
            ),
        );
    }
}
