<?php

namespace Zenstruck\Browser\Assert\Assertion;

use Zenstruck\Browser\Assert\Assertion;
use Zenstruck\Browser\Assert\Exception\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Fail implements Assertion
{
    private string $message;

    public function __construct(string $message, ...$args)
    {
        $this->message = \sprintf($message, ...$args);
    }

    public function __invoke(): void
    {
        throw new AssertionFailed($this->message);
    }
}
