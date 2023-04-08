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
 *
 * @phpstan-type PartsToMatch array{
 *     scheme?: string,
 *     host?: string,
 *     port?: int<0,65535>,
 *     user?: string,
 *     pass?: string,
 *     path?: string,
 *     query?: string,
 *     fragment?: string,
 * }
 */
final class SameUrlAssertion implements Negatable
{
    /**
     * @param PartsToMatch $partsToMatch
     */
    public function __construct(private string $current, private string $expected, private array $partsToMatch = [])
    {
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

    /**
     * @return array<string,string>
     */
    private function context(): array
    {
        return [
            'current' => $this->current,
            'expected' => $this->expected,
            'parts' => \implode(', ', $this->partsToMatch),
        ];
    }

    /**
     * @return PartsToMatch
     */
    private function parseUrl(string $url): array
    {
        $parts = \parse_url(\urldecode($url)) ?: throw new \RuntimeException(\sprintf('Failed to parse URL: "%s".', $url));
        if (!$this->partsToMatch) {
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
