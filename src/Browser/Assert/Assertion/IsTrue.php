<?php

namespace Zenstruck\Browser\Assert\Assertion;

use Zenstruck\Browser\Assert\Assertion;
use Zenstruck\Browser\Assert\Exception\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IsTrue implements Assertion
{
    private bool $expression;
    private string $message;

    public function __construct(bool $expression, string $message, ...$args)
    {
        $this->expression = $expression;
        $this->message = \sprintf($message, ...$args);
    }

    public function __invoke(): void
    {
        if (!$this->expression) {
            throw new AssertionFailed($this->message);
        }
    }
}
