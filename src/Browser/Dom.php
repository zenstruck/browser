<?php

namespace Zenstruck\Browser;

use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Assert;

/**
 * @mixin Crawler
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Dom implements \IteratorAggregate, \Countable
{
    private Crawler $crawler;

    private function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __call(string $name, array $arguments)
    {
        if (!\method_exists($this->crawler, $name)) {
            throw new \BadMethodCallException(\sprintf('Method "%s" does not exist on "%s".', $name, \get_class($this->crawler)));
        }

        $ret = $this->crawler->{$name}(...$arguments);

        return $ret instanceof Crawler ? new self($ret) : $ret;
    }

    /**
     * @param Crawler|self $crawler
     */
    public static function wrap($crawler): self
    {
        return $crawler instanceof self ? $crawler : new self($crawler);
    }

    public static function fromString(string $value): self
    {
        return self::wrap(new Crawler($value));
    }

    public function crawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * Similar to {@see Crawler::getIterator()} but iterate over the nodes
     * as {@see self} instead of {@see \DOMNode}.
     *
     * @return \Traversable|self[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->each(fn(Crawler $c) => new self($c)));
    }

    public function count(): int
    {
        return $this->crawler->count();
    }

    /**
     * @param string $selector XPath or CSS expression
     */
    public function find(string $selector): self
    {
        try {
            return $this->filter($selector);
        } catch (\Exception $e) {
            // could not covert selector to xpath, try as xpath directly
            try {
                \set_error_handler(static function($errno, $errstr, $errfile, $errline) {
                    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
                });

                return $this->filterXPath($selector);
            } finally {
                \restore_error_handler();
            }
        }
    }

    public function assertSee(string $expected): self
    {
        Assert::that($this->text())->contains($expected, 'Expected to see text "{needle}".');

        return $this;
    }

    public function assertNotSee(string $expected): self
    {
        Assert::that($this->text())->doesNotContain($expected, 'Expected to not see text "{needle}".');

        return $this;
    }

    public function assertSeeElement(string $selector): self
    {
        Assert::that($this->find($selector))
            ->isNotEmpty('Expected to find element matching "{selector}".', [
                'selector' => $selector,
                'actual' => $this->html(),
            ])
        ;

        return $this;
    }

    public function assertNotSeeElement(string $selector): self
    {
        Assert::that($this->find($selector))
            ->isEmpty(
                'Expected to not find element matching "{selector}" but found {count}.',
                ['selector' => $selector, 'actual' => $this->html()]
            )
        ;

        return $this;
    }

    public function assertSeeIn(string $selector, string $expected): self
    {
        $this->assertSeeElement($selector);

        Assert::that($this->find($selector)->text())->contains(
            $expected,
            'Expected to see text "{needle}" in element matching "{selector}".',
            ['selector' => $selector]
        );

        return $this;
    }

    public function assertNotSeeIn(string $selector, string $expected): self
    {
        $this->assertSeeElement($selector);

        Assert::that($this->find($selector)->text())->doesNotContain(
            $expected,
            'Expected to not see text "{needle}" in element matching "{selector}".',
            ['selector' => $selector, 'haystack' => $this->html()]
        );

        return $this;
    }

    public function assertElementCount(string $selector, int $expected): self
    {
        Assert::that($this->find($selector))
            ->hasCount($expected, 'Expected to find {expected} element(s) matching "{selector}" but found {actual}.',
                ['selector' => $selector, 'haystack' => $this->html()]
            )
        ;

        return $this;
    }

    public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        $this->assertElementHasAttribute($selector, $attribute);

        Assert::that($this->find($selector)->attr($attribute))->contains(
            $expected,
            'Expected "{attribute}" attribute for element matching "{selector}" to contain "{needle}".',
            ['attribute' => $attribute, 'selector' => $selector]
        );

        return $this;
    }

    public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        $this->assertElementHasAttribute($selector, $attribute);

        Assert::that($this->find($selector)->attr($attribute))->doesNotContain(
            $expected,
            'Expected "{attribute}" attribute for element matching "{selector}" to not contain "{needle}".',
            ['attribute' => $attribute, 'selector' => $selector]
        );

        return $this;
    }

    public function assertElementHasAttribute(string $selector, string $attribute): self
    {
        $this->assertSeeElement($selector);

        Assert::that($this->find($selector)->attr($attribute))->isNotEmpty(
            'Expected element matching "{selector}" to have "{attribute}" attribute.',
            ['selector' => $selector, 'attribute' => $attribute]
        );

        return $this;
    }
}
