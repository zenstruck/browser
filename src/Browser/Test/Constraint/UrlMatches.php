<?php

namespace Zenstruck\Browser\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class UrlMatches extends Constraint
{
    private array $partsToMatch;
    private array $url;

    public function __construct(string $url, array $partsToMatch = [])
    {
        $this->partsToMatch = $partsToMatch;
        $this->url = $this->parseUrl($url);
    }

    public function toString(): string
    {
        return 'matches '.$this->exporter()->export($this->url);
    }

    protected function matches($other): bool
    {
        return $this->parseUrl($other) === $this->url;
    }

    protected function failureDescription($other): string
    {
        return parent::failureDescription($this->parseUrl($other));
    }

    private function parseUrl(string $url): array
    {
        $parts = \parse_url($url);

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
