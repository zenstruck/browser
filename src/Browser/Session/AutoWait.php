<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session;

use Behat\Mink\Exception\ExpectationException;

/**
 * Retries an operation until it succeeds or the timeout expires.
 *
 * Whole operations are retried, never the individual element lookups inside them: a lookup that
 * waited would invert the meaning of the "not" assertions, and would make each miss of a
 * speculative lookup (see Browser::getClickableElement()) cost the full timeout.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class AutoWait
{
    /**
     * Turbo swaps a body in tens of milliseconds: polling any slower spends most of the wait
     * asleep after the page has already caught up.
     */
    private const POLL_INTERVAL = 50_000;

    /**
     * @param int $timeout milliseconds, 0 or less to disable
     */
    public function __construct(private int $timeout = 0)
    {
    }

    /**
     * The configured budget, or null when waiting is off.
     */
    public function timeout(): ?int
    {
        return $this->timeout > 0 ? $this->timeout : null;
    }

    /**
     * Retry until the assertion stops failing. "Not" assertions come out right for free: one that
     * already holds passes on the first attempt, one whose element is on its way out waits for it.
     */
    public function assertion(callable $assertion): mixed
    {
        $deadline = $this->deadline();

        while (true) {
            try {
                return $assertion();
            } catch (ExpectationException $e) {
                if (\microtime(true) >= $deadline) {
                    throw $e;
                }
            }

            \usleep(self::POLL_INTERVAL);
        }
    }

    /**
     * Retry until the lookup finds something. The element is only resolved here: acting on it still
     * goes through Playwright, which applies its own actionability wait.
     */
    public function lookup(callable $lookup): mixed
    {
        $deadline = $this->deadline();

        while (true) {
            if ($found = $lookup()) {
                return $found;
            }

            if (\microtime(true) >= $deadline) {
                return $found;
            }

            \usleep(self::POLL_INTERVAL);
        }
    }

    private function deadline(): float
    {
        // a timeout of 0 or less puts the deadline in the past, so every operation gets one attempt
        return \microtime(true) + ($this->timeout / 1000);
    }
}
