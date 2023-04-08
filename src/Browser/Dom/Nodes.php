<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 *
 * @implements \IteratorAggregate<Node>
 */
final class Nodes implements \IteratorAggregate, \Countable
{
    private function __construct(private Crawler $crawler, private ?Session $session)
    {
    }

    public static function create(Crawler $crawler, ?Session $session): self
    {
        return new self($crawler, $session);
    }

    public function crawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * @param SelectorType|null $selector
     */
    public function first(Selector|string|callable|null $selector = null): ?Node
    {
        if ($selector) {
            return $this->filter($selector)->first();
        }

        return $this->count() ? Node::create($this->crawler->first(), $this->session) : null;
    }

    public function last(): ?Node
    {
        return $this->count() ? Node::create($this->crawler->last(), $this->session) : null;
    }

    /**
     * @param SelectorType $selector
     */
    public function filter(Selector|string|callable $selector): self
    {
        return self::create(Selector::wrap($selector)->filter($this->crawler), $this->session);
    }

    /**
     * @template Input of Node
     * @template Return
     *
     * @param callable(Input):Return $callback
     *
     * @return Return[]
     */
    public function map(callable $callback): array
    {
        return \array_map($callback, \iterator_to_array($this)); // @phpstan-ignore-line
    }

    public function text(): ?string
    {
        try {
            return $this->crawler->text();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function html(): ?string
    {
        try {
            return $this->crawler->html();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function getIterator(): \Traversable
    {
        for ($i = 0; $i < $this->count(); ++$i) {
            yield Node::create($this->crawler->eq($i), $this->session);
        }
    }

    public function count(): int
    {
        return \count($this->crawler);
    }

    public function dump(): self
    {
        foreach ($this as $node) {
            $node->dump();
        }

        return $this;
    }

    public function dd(): void
    {
        $this->dump();

        exit(1);
    }
}
