<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session\Playwright;

use Playwright\Exception\TimeoutException;
use Playwright\Page\PageInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Wait
{
    public function __construct(private PageInterface $page, private int $timeout)
    {
    }

    public function condition(string $predicate, mixed $arguments = null): void
    {
        try {
            $this->page->waitForFunction($predicate, $arguments, ['timeout' => $this->timeout]);
        } catch (TimeoutException) {
            // The following assertion supplies the existing failure message.
        }
    }

    public function element(string $xpath, string $state): bool
    {
        try {
            $this->page->waitForSelector('xpath='.$xpath, ['state' => $state, 'timeout' => $this->timeout]);

            return true;
        } catch (TimeoutException) {
            return false;
        }
    }

    /**
     * SameUrlAssertion decodes before parsing, including encoded URL delimiters.
     */
    public function url(string $expected, array $parts, bool $negated): void
    {
        $this->condition(<<<'JS'
            ([expected, parts, negated]) => {
                const parse = source => {
                    const decoded = source.replace(/\+/g, ' ').replace(/(?:%[0-9a-f]{2})+/gi, encoded => {
                        try {
                            return decodeURIComponent(encoded);
                        } catch {
                            return encoded;
                        }
                    });
                    const match = decoded.match(/^(?:([^:/?#]+):)?(?:\/\/(?:(?:([^:@/?#]*)(?::([^@/?#]*))?)@)?(\[[^\]]+\]|[^:/?#]*)(?::([0-9]+))?)?([^?#]*)(?:\?([^#]*))?(?:#(.*))?$/);
                    const names = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'];
                    return Object.fromEntries(names.flatMap((name, index) => {
                        const value = match[index + 1];

                        if (value === undefined || (parts.length && !parts.includes(name))) return [];

                        return [[name, name === 'port' ? Number(value) : value]];
                    }));
                };
                const actual = parse(location.href);
                const target = parse(expected);
                const keys = Object.keys(actual);
                const equal = keys.length === Object.keys(target).length && keys.every(key => actual[key] === target[key]);
                return negated ? !equal : equal;
            }
            JS, [$expected, \array_values($parts), $negated]);
    }
}
