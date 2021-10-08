<?php

namespace Zenstruck\Browser;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Zenstruck\Assert;
use Zenstruck\Browser\Dom\Assertion\FieldCheckedAssertion;
use Zenstruck\Browser\Dom\Assertion\FieldSelectedAssertion;
use Zenstruck\Browser\Dom\Form\Field;

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
     * Override to normalize/remove deprecation in 4.4.
     */
    public function text(?string $default = null, bool $normalizeWhitespace = true): string
    {
        return $this->crawler->text($default, $normalizeWhitespace);
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

    /**
     * Similar to {@see BaseCrawler::form()} but with a few differences:
     * 1. If not currently on a form node, attempt to find the closest.
     * 2. todo Cache the form object so it can be manipulated by the form manipulation methods.
     */
    public function form(?array $values = null, ?string $method = null): Form
    {
        if (!$element = $this->closest('form')) {
            throw new \InvalidArgumentException('Unable to find form in DOM tree.');
        }

        return $element->crawler->form($values, $method);
    }

    public function field(): Field
    {
        return Field::create($this);
    }

    /**
     * @param string $selector css, xpath, name, id, or label text
     */
    public function findFormField(string $selector): ?Field
    {
        try {
            return $this->selectField($selector)->field();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @see findFormField()
     *
     * @throws \InvalidArgumentException If not found
     */
    public function getFormField(string $selector): Field
    {
        if (!$field = $this->findFormField($selector)) {
            throw new \InvalidArgumentException("Form field matching \"{$selector}\" not found.");
        }

        return $field;
    }

    /**
     * @param string $selector css, xpath, name, id, or label text
     */
    public function selectField(string $selector): self
    {
        // try css/xpath
        try {
            $element = $this->find($selector);
        } catch (\Exception $e) {
            $element = [];
        }

        if (!\count($element)) {
            // try by name
            $element = $this->filter("form [name=\"{$selector}\"]");
        }

        if (!\count($element)) {
            // try by id
            $element = $this->filterXPath(".//*[@id=\"{$selector}\"]");
        }

        if (!\count($element) && \count($label = $this->filterXPath(".//label[contains(normalize-space(),\"{$selector}\")]"))) {
            // try by label text
            if ($attr = $label->attr('for')) {
                // <label for="input-id">
                $element = $this->filter("#{$attr}");
            } else {
                // <label><input/></label>
                $element = $label->filter('[name]');
            }
        }

        return $element;
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

    public function assertFieldEquals(string $selector, string $expected): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->equals(
                $expected,
                'Expected form field matching "{selector}" to equal "{expected}".',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertFieldNotEquals(string $selector, string $expected): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->isNotEqualTo(
                $expected,
                'Expected form field matching "{selector}" to not equal "{expected}".',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertFieldContains(string $selector, string $expected): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->contains(
                $expected,
                'Expected form field matching "{selector}" to contain "{needle}".',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertFieldNotContains(string $selector, string $expected): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->doesNotContain(
                $expected,
                'Expected form field matching "{selector}" to not contain "{needle}".',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertFieldEmpty(string $selector): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->isEmpty(
                'Expected form field matching "{selector}" to be empty.',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertFieldNotEmpty(string $selector): self
    {
        Assert::that(Assert::try(fn() => $this->getFormField($selector))->getValue())
            ->isNotEmpty(
                'Expected form field matching "{selector}" to not be empty.',
                ['selector' => $selector]
            )
        ;

        return $this;
    }

    public function assertChecked(string $selector): self
    {
        Assert::run(new FieldCheckedAssertion($this, $selector));

        return $this;
    }

    public function assertNotChecked(string $selector): self
    {
        Assert::not(new FieldCheckedAssertion($this, $selector));

        return $this;
    }

    public function assertSelected(string $selector, ?string $expected = null): self
    {
        if (null === $expected) {
            return $this->assertChecked($selector);
        }

        Assert::run(new FieldSelectedAssertion($this, $selector, $expected));

        return $this;
    }

    public function assertNotSelected(string $selector, ?string $expected = null): self
    {
        if (null === $expected) {
            return $this->assertNotChecked($selector);
        }

        Assert::not(new FieldSelectedAssertion($this, $selector, $expected));

        return $this;
    }
}
