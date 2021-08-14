<?php

namespace Zenstruck\Browser\Assertion;

use Behat\Mink\Exception\ExpectationException;
use Zenstruck\Assert\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class MinkAssertion
{
    /** @var callable */
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
            AssertionFailed::throw($e->getMessage());
        }
    }
}
