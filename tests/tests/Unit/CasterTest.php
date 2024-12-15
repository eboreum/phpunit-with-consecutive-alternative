<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use Eboreum\PhpunitWithConsecutiveAlternative\Caster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(Caster::class)]
class CasterTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testGetInstanceWorks(): void
    {
        $a = Caster::getInstance();
        $b = Caster::getInstance();

        $this->assertSame($a, $b);
    }

    public function testCreateWorks(): void
    {
        $a = Caster::create();
        $b = Caster::create();

        $this->assertNotSame($a, $b);
    }
}
