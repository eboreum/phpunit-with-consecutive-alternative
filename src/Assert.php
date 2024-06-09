<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\Caster\Caster;

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
        if (false === class_exists($className)) {
            throw new AssertException(
                sprintf(
                    'Argument $className = %s references a class, which does not exist',
                    Caster::getInstance()->castTyped($className),
                ),
            );
        }
    }

    /**
     * @throws AssertException
     */
    public static function isObject(mixed $value): void
    {
        if (false === is_object($value)) {
            throw new AssertException(
                sprintf(
                    'Argument $value = %s must be an object, but it is not',
                    Caster::getInstance()->castTyped($value),
                ),
            );
        }
    }

    /**
     * @param class-string $className
     *
     * @throws AssertException
     */
    public static function isInstanceOf(object $object, string $className): void
    {
        self::classExists($className);

        if (false === is_a($object, $className)) {
            throw new AssertException(
                sprintf(
                    'Argument $object = %s must an instance of \\%s, but it is not',
                    Caster::getInstance()->castTyped($object),
                    $className,
                ),
            );
        }
    }

    /**
     * @param array<mixed> $array
     *
     * @throws AssertException
     */
    public static function keyExists(array $array, int|string $key): void
    {
        if (false === array_key_exists($key, $array)) {
            throw new AssertException(
                sprintf(
                    'Argument $array = %s does not have a key %s',
                    Caster::getInstance()->castTyped($array),
                    Caster::getInstance()->cast($key),
                ),
            );
        }
    }
}
