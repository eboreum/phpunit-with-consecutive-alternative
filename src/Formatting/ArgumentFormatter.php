<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative\Formatting;

use Eboreum\Caster\Contract\CasterInterface;
use Eboreum\PhpunitWithConsecutiveAlternative\Caster;

use function is_int;
use function preg_match;
use function sprintf;

class ArgumentFormatter
{
    private CasterInterface $caster;

    public function __construct(?CasterInterface $caster = null)
    {
        $this->caster = $caster ?? Caster::getInstance();
    }

    /**
     * @param int|non-empty-string $argumentName
     *
     * @return non-empty-string
     */
    public function formatArgumentName(int|string $argumentName): string
    {
        if (is_int($argumentName) || 1 === preg_match('/^\d+$/', $argumentName)) {
            return sprintf('{%s}', $argumentName);
        }

        if (1 === preg_match('/[\x00\s]/', $argumentName)) {
            return sprintf('${%s}', $this->caster->cast($argumentName));
        }

        return sprintf('$%s', $argumentName);
    }

    /**
     * @return non-empty-string
     */
    public function formatArgumentValue(mixed $argumentValue): string
    {
        /** @var non-empty-string $formatted */
        $formatted = $this->caster->castTyped($argumentValue);

        return $formatted;
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return array<int, string>
     */
    public function formatArguments(array $arguments): array
    {
        /** @var array<string> $formatted */
        $formatted = [];

        foreach ($arguments as $k => $v) {
            $formatted[] = sprintf(
                '%s = %s',
                $this->formatArgumentName($k),
                $this->formatArgumentValue($v),
            );
        }

        return $formatted;
    }
}
