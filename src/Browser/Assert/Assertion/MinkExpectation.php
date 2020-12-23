<?php

namespace Zenstruck\Browser\Assert\Assertion;

use Behat\Mink\Exception\ExpectationException;
use Zenstruck\Browser\Assert\Assertion;
use Zenstruck\Browser\Assert\Exception\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MinkExpectation implements Assertion
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(): void
    {
        try {
            ($this->callback)();
        } catch (ExpectationException $e) {
            throw new AssertionFailed($e->getMessage(), 0, $e);
        }
    }
}
