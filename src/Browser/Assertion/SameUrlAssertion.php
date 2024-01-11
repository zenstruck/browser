<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Assertion;

use Zenstruck\Assert\Assertion\Negatable;
use Zenstruck\Assert\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class SameUrlAssertion implements Negatable
{
    private string $current;
    private string $expected;
    private array $partsToMatch;

    public function __construct(string $current, string $expected, array $partsToMatch = [])
    {
        $this->current = $current;
        $this->expected = $expected;
        $this->partsToMatch = $partsToMatch;
    }

    public function __invoke(): void
    {
        $parsedCurrent = $this->parseUrl($this->current);
        $parsedExpected = $this->parseUrl($this->expected);

        if ($parsedCurrent === $parsedExpected) {
            return;
        }

        AssertionFailed::throw(
            'Expected current URL ({current}) to be "{expected}" (comparing parts: "{parts}").',
            \array_merge($this->context(), [
                'compare_actual' => $parsedCurrent,
                'compare_expected' => $parsedExpected,
            ]),
        );
    }

    public function notFailure(): AssertionFailed
    {
        return new AssertionFailed(
            'Expected current URL ({current}) to not be "{expected}" (comparing parts: "{parts}").',
            $this->context(),
        );
    }

    private function context(): array
    {
        return [
            'current' => $this->current,
            'expected' => $this->expected,
            'parts' => \implode(', ', $this->partsToMatch),
        ];
    }

    private function parseUrl(string $url): array
    {
        $parts = \parse_url(\urldecode($url));

        if (empty($this->partsToMatch)) {
            return $parts;
        }

        foreach (\array_keys($parts) as $part) {
            if (!\in_array($part, $this->partsToMatch, true)) {
                unset($parts[$part]);
            }
        }

        return $parts;
    }
}
