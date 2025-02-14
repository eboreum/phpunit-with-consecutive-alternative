<?php

declare(strict_types=1);

namespace Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\Caster\Caster as EboreumCaster;
use Eboreum\Caster\CharacterEncoding;
use Eboreum\Caster\Collection\Formatter\ObjectFormatterCollection;
use Eboreum\Caster\Contract\CharacterEncodingInterface;
use Eboreum\Caster\Contract\Formatter\ObjectFormatterInterface;
use Eboreum\Caster\Formatter\Object_\ThrowableFormatter;

class Caster extends EboreumCaster
{
    private static ?self $instance = null;

    public static function getInstance(): Caster
    {
        if (null === self::$instance) {
            self::$instance = self::create();
        }

        return self::$instance;
    }

    public static function create(?CharacterEncodingInterface $characterEncoding = null): static
    {
        if (null === $characterEncoding) {
            $characterEncoding = CharacterEncoding::getInstance();
        }

        $self = new static($characterEncoding);

        /** @var array<ObjectFormatterInterface> $formatters */
        $formatters = [new ThrowableFormatter()];

        $self = $self->withCustomObjectFormatterCollection(new ObjectFormatterCollection($formatters));

        return $self;
    }
}
