<?php

namespace Zenstruck\Browser\Extension;

use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractiveExtension
{
    use DomExtension;

    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        $this->session()->visit($uri);

        return $this;
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->session()->page()->fillField($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function checkField(string $selector): self
    {
        $field = $this->session()->page()->findField($selector);

        if ($field && 'radio' === \mb_strtolower((string) $field->getAttribute('type'))) {
            $this->session()->page()->selectFieldOption($selector, (string) $field->getAttribute('value'));

            return $this;
        }

        $this->session()->page()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function uncheckField(string $selector): self
    {
        $this->session()->page()->uncheckField($selector);

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
        $this->session()->page()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->session()->page()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    /**
     * @param string[]|string $filename string: single file
     *                                  array: multiple files
     *
     * @return static
     */
    final public function attachFile(string $selector, $filename): self
    {
        foreach ((array) $filename as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $file));
            }
        }

        $this->session()->page()->attachFileToField($selector, $filename);

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @return static
     */
    final public function click(string $selector): self
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
        $field = $this->session()->assert()->fieldExists($selector);

        Assert::that((array) $field->getValue())->contains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        $field = $this->session()->assert()->fieldExists($selector);

        Assert::that((array) $field->getValue())->doesNotContain($expected);

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
}
