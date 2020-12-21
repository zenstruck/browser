<?php

namespace Zenstruck\Browser\Assert\Handler;

use Zenstruck\Browser\Assert\Exception\AssertionFailed;
use Zenstruck\Browser\Assert\Handler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DefaultHandler implements Handler
{
    public function onSuccess(): void
    {
        // noop
    }

    public function onFailure(AssertionFailed $exception): void
    {
        throw $exception;
    }
}
