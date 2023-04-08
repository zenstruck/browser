<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;
use Zenstruck\Browser\Dom\Exception\RuntimeException;
use Zenstruck\Browser\Dom\Expectation;
use Zenstruck\Browser\Dom\Node;
use Zenstruck\Browser\Dom\Nodes;
use Zenstruck\Browser\Dom\Selector;
use Zenstruck\Browser\Dom\Session;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 */
final class Dom
{
    private Crawler $crawler;
    private Expectation $expectation;

    public function __construct(string|Crawler $crawler, private ?Session $session = null)
    {
        if (\is_string($crawler)) {
            $crawler = new Crawler($crawler);
        }

        $this->crawler = $crawler;
    }

    /**
     * @param SelectorType $selector
     */
    public function find(Selector|string|callable $selector): ?Node
    {
        if ($this->crawler instanceof PantherCrawler && 'html' === $selector) {
            return Node::create($this->crawler, $this->session);
        }

        return Nodes::create($this->crawler, $this->session)->first($selector);
    }

    /**
     * @param SelectorType $selector
     */
    public function findOrFail(Selector|string|callable $selector): Node
    {
        return $this->find($selector) ?? throw new RuntimeException(\sprintf('Could not find node with selector "%s".', Selector::wrap($selector)));
    }

    /**
     * @param SelectorType $selector
     */
    public function findAll(Selector|string|callable $selector): Nodes
    {
        return Nodes::create($this->crawler, $this->session)->filter($selector);
    }

    public function crawler(): Crawler
    {
        return $this->crawler;
    }

    public function expect(): Expectation
    {
        return $this->expectation ??= new Expectation($this);
    }

    /**
     * @param SelectorType|null $selector
     */
    public function dump(Selector|string|callable|null $selector = null): static
    {
        $dump = static fn(mixed $what) => \function_exists('dump') ? dump($what) : \var_dump($what);

        null === $selector ? $dump($this->crawler->outerHtml()) : $this->findAll($selector)->dump();

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function dd(Selector|string|callable|null $selector = null): void
    {
        $this->dump($selector);

        exit(1);
    }
}
