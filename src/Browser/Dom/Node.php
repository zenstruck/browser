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
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;
use Zenstruck\Browser\Dom\Exception\RuntimeException;
use Zenstruck\Browser\Dom\Node\Attributes;
use Zenstruck\Browser\Dom\Node\Form;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 */
class Node
{
    public const SELECTOR = '*';

    private function __construct(private Crawler $crawler, protected ?Session $session)
    {
    }

    public static function create(Crawler $crawler, ?Session $session): self
    {
        $node = new self($crawler, $session);
        $tag = \mb_strtolower($node->tag());

        return match (true) {
            'form' === $tag => new Form($crawler, $session),
            'label' === $tag => new Form\Label($crawler, $session),
            'textarea' === $tag => new Form\Field\Textarea($crawler, $session),
            'input' === $tag && $node->attributes()->is('type', 'checkbox') => new Form\Field\Checkbox($crawler, $session),
            'input' === $tag && $node->attributes()->is('type', 'radio') => new Form\Field\Radio($crawler, $session),
            'input' === $tag && $node->attributes()->is('type', 'file') => new Form\Field\File($crawler, $session),
            'input' === $tag && $node->attributes()->is('type', 'submit', 'button', 'reset', 'image') => new Form\Button($crawler, $session),
            'button' === $tag => new Form\Button($crawler, $session),
            'input' === $tag => new Form\Field\Input($crawler, $session),
            'option' === $tag => new Form\Field\Select\Option($crawler, $session),
            'select' === $tag && $node->attributes()->has('multiple') => new Form\Field\Select\Multiselect($crawler, $session),
            'select' === $tag => new Form\Field\Select\Combobox($crawler, $session),
            default => $node,
        };
    }

    public function crawler(): Crawler
    {
        return $this->crawler;
    }

    public function element(): \DOMElement
    {
        $element = $this->normalizedCrawler()->getNode(0);

        if (!$element instanceof \DOMElement) {
            throw new RuntimeException('Unable to get attributes from non-element node.');
        }

        return $element;
    }

    public function tag(): string
    {
        return $this->crawler->nodeName();
    }

    public function attributes(): Attributes
    {
        return new Attributes($this->element());
    }

    public function text(): string
    {
        return $this->crawler->text();
    }

    public function directText(): string
    {
        return $this->crawler->innerText();
    }

    public function html(): ?string
    {
        if ($this->crawler instanceof PantherCrawler) {
            return $this->crawler->html();
        }

        if ('' === $html = $this->crawler->outerHtml()) {
            return null;
        }

        return $html;
    }

    public function innerHtml(): string
    {
        return $this->crawler->html();
    }

    public function parent(): ?self
    {
        return Nodes::create($this->crawler->ancestors(), $this->session)->first();
    }

    public function next(): ?self
    {
        return Nodes::create($this->crawler->nextAll(), $this->session)->first();
    }

    public function previous(): ?self
    {
        return Nodes::create($this->crawler->previousAll(), $this->session)->first();
    }

    public function closest(string $selector): ?self
    {
        $closest = $this->crawler->closest($selector);

        return $closest ? self::create($closest, $this->session) : null;
    }

    /**
     * @param SelectorType $selector
     */
    public function ancestor(Selector|string|callable $selector): ?self
    {
        return $this->ancestors($selector)->first();
    }

    /**
     * @param SelectorType|null $selector
     */
    public function ancestors(Selector|string|callable|null $selector = null): Nodes
    {
        return $this->applySelectorTo($this->crawler->ancestors(), $selector);
    }

    /**
     * @param SelectorType|null $selector
     */
    public function siblings(Selector|string|callable|null $selector = null): Nodes
    {
        return $this->applySelectorTo($this->crawler->siblings(), $selector);
    }

    /**
     * @param SelectorType|null $selector
     */
    public function children(Selector|string|callable|null $selector = null): Nodes
    {
        return $this->applySelectorTo($this->crawler->children(), $selector);
    }

    /**
     * @param SelectorType $selector
     */
    public function descendent(Selector|string|callable $selector): ?self
    {
        return $this->descendents($selector)->first();
    }

    /**
     * @param SelectorType|null $selector
     */
    public function descendents(Selector|string|callable|null $selector = null): Nodes
    {
        return $this->applySelectorTo($this->crawler, $selector ?? Selector::xpath('descendant::*'));
    }

    /**
     * @template T of self
     *
     * @param class-string<T> $type
     */
    public function is(string $type): bool
    {
        return $this instanceof $type;
    }

    /**
     * @template T of self
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    public function ensure(string $type): self
    {
        if ($this instanceof $type) {
            return $this;
        }

        throw new RuntimeException(\sprintf('Expected "%s", got "%s".', $type, $this::class));
    }

    public function id(): ?string
    {
        return $this->attributes()->get('id');
    }

    public function click(): void
    {
        $this->ensureSession()->click($this);
    }

    public function dump(): static
    {
        \function_exists('dump') ? dump($this->html()) : \var_dump($this->html());

        return $this;
    }

    public function isVisible(): bool
    {
        if ($this->crawler instanceof PantherCrawler) {
            return $this->crawler->isDisplayed();
        }

        return true;
    }

    public function dd(): void
    {
        $this->dump();

        exit(1);
    }

    protected function ensureSession(): Session
    {
        return $this->session ?? throw new RuntimeException('No interactive session available.');
    }

    /**
     * @param SelectorType|null $selector
     */
    private function applySelectorTo(Crawler $crawler, Selector|string|callable|null $selector = null): Nodes
    {
        $nodes = Nodes::create($crawler, $this->session);

        return $selector ? $nodes->filter($selector) : $nodes;
    }

    private function normalizedCrawler(): Crawler
    {
        if ($this->crawler instanceof PantherCrawler) {
            return (new Crawler($this->crawler->html()))->filter($this->tag());
        }

        return $this->crawler;
    }
}
