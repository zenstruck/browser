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

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Callback\Parameter;

/**
 * @phpstan-require-extends Browser
 */
trait Htmlable
{
    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        $this->session()->request('GET', $uri);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHtml(): self
    {
        return $this->assertContentType('html');
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        $this->session()->assert()->pageTextContains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        $this->session()->assert()->pageTextNotContains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeIn(string $selector, string $expected): self
    {
        $this->session()->assert()->elementTextContains('css', $selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        $this->session()->assert()->elementTextNotContains('css', $selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeElement(string $selector): self
    {
        $this->session()->assert()->elementExists('css', $selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeElement(string $selector): self
    {
        $this->session()->assert()->elementNotExists('css', $selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementCount(string $selector, int $count): self
    {
        $this->session()->assert()->elementsCount('css', $selector, $count);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        $this->session()->assert()->elementAttributeContains('css', $selector, $attribute, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        $this->session()->assert()->elementAttributeNotContains('css', $selector, $attribute, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->getField($selector)->setValue($value);

        return $this;
    }

    /**
     * @return static
     */
    final public function checkField(string $selector): self
    {
        $field = $this->getField($selector);

        if ('radio' === \mb_strtolower((string) $field->getAttribute('type'))) {
            $field->selectOption((string) $field->getAttribute('value'));

            return $this;
        }

        $field->check();

        return $this;
    }

    /**
     * @return static
     */
    final public function uncheckField(string $selector): self
    {
        $this->getField($selector)->uncheck();

        return $this;
    }

    /**
     * Select Radio, check checkbox, select single/multiple values.
     *
     * @param string|array|null $value null: check radio/checkbox
     *                                 string: single value
     *                                 array: multiple values
     *
     * @return static
     */
    final public function selectField(string $selector, $value = null): self
    {
        if (\is_array($value)) {
            return $this->selectFieldOptions($selector, $value);
        }

        if (\is_string($value)) {
            return $this->selectFieldOption($selector, $value);
        }

        return $this->checkField($selector);
    }

    /**
     * @return static
     */
    final public function selectFieldOption(string $selector, string $value): self
    {
        $this->getField($selector)->selectOption($value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        $field = $this->getField($selector);

        if (!$values) {
            $field->setValue($values);

            return $this;
        }

        foreach ($values as $value) {
            $field->selectOption($value, true);
        }

        return $this;
    }

    /**
     * @param string|string[] $filename string: single file
     *                                  array: multiple files
     *
     * @return static
     */
    final public function attachFile(string $selector, array|string $filename): self
    {
        foreach ((array) $filename as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $file));
            }

            $this->getField($selector)->attachFile($file);
        }

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @return static
     */
    final public function click(string $selector): self
    {
        $element = $this->getClickableElement($selector);

        $element->click();

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldEquals(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueEquals($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldNotEquals(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueNotEquals($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSelected(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueContains($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueContains($selector, $expected, true);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertChecked(string $selector): self
    {
        $this->session()->assert()->checkboxChecked($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotChecked(string $selector): self
    {
        $this->session()->assert()->checkboxNotChecked($selector);

        return $this;
    }

    /**
     * Macro for ->interceptRedirects()->withProfiling()->click().
     *
     * Useful for submitting a form and making assertions on the
     * redirect response.
     *
     * @return static
     */
    final public function clickAndIntercept(string $selector): self
    {
        return $this
            ->interceptRedirects()
            ->withProfiling()
            ->click($selector)
        ;
    }

    protected function getClickableElement(string $selector): NodeElement
    {
        // try button
        $element = $this->session()->page()->findButton($selector);

        if (!$element) {
            // try link
            $element = $this->session()->page()->findLink($selector);
        }

        if (!$element) {
            // try by css
            $element = $this->session()->page()->find('css', $selector);
        }

        if (!$element) {
            Assert::fail('Clickable element "%s" not found.', [$selector]);
        }

        if (!$element->isVisible()) {
            Assert::fail('Clickable element "%s" is not visible.', [$selector]);
        }

        if ($button = $this->session()->page()->findButton($selector)) {
            if (!$button->isVisible()) {
                Assert::fail('Button "%s" is not visible.', [$selector]);
            }
        }

        return $element;
    }

    protected function getField(string $selector): NodeElement
    {
        if (!$field = $this->session()->page()->findField($selector)) {
            throw new ElementNotFoundException($this->session()->getDriver(), 'form field', 'id|name|label|value', $selector);
        }

        return $field;
    }

    protected function useParameters(): array
    {
        return [
            ...parent::useParameters(),
            Parameter::typed(Component::class, Parameter::factory(fn(string $class) => new $class($this))),
        ];
    }
}
