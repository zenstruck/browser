<?php

namespace Zenstruck\Browser\Assert\Handler;

use PHPUnit\Framework\Assert as PHPUnit;
use Zenstruck\Browser\Assert\Exception\AssertionFailed;
use Zenstruck\Browser\Assert\Handler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PHPUnitHandler implements Handler
{
    public function onSuccess(): void
    {
        PHPUnit::assertTrue(true); // trigger an assertion
    }

    public function onFailure(AssertionFailed $exception): void
    {
        PHPUnit::fail($exception->getMessage());
    }
}
