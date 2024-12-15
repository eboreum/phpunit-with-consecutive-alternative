<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

/**
 * A class for storing a return value and (potentially) arguments.
 */
readonly class MethodCallExpectation
{
    /** @var array<mixed> */
    public array $arguments;

    public function __construct(
        public mixed $returnValue,
        mixed ...$arguments
    ) {
        $this->arguments = $arguments;
    }
}
