<?php

namespace Zenstruck\Browser\Assert\Assertion;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Url
{
    private array $current;
    private array $partsToMatch;

    public function __construct(string $current, array $partsToMatch = [])
    {
        $this->partsToMatch = $partsToMatch;
        $this->current = $this->parseUrl($current);
    }

    public function matches(string $expected): IsTrue
    {
        $expected = $this->parseUrl($expected);

        return new IsTrue(
            $expected === $this->current,
            'The current url: %s does not match expected: %s.',
            self::jsonEncode($this->current),
            self::jsonEncode($expected)
        );
    }

    public function notMatches(string $expected): IsTrue
    {
        $expected = $this->parseUrl($expected);

        return new IsTrue(
            $expected !== $this->current,
            'The current url: %s matches expected: %s but it should not.',
            self::jsonEncode($this->current),
            self::jsonEncode($expected)
        );
    }

    private static function jsonEncode(array $parts): string
    {
        return \json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
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
