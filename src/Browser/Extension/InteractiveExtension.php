<?php

namespace Zenstruck\Browser\Extension;

use Behat\Mink\Exception\ExpectationException;
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
        $this->minkSession()->visit($uri);

        return $this;
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->documentElement()->fillField($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function checkField(string $selector): self
    {
        $field = $this->documentElement()->findField($selector);

        if ($field && 'radio' === \mb_strtolower((string) $field->getAttribute('type'))) {
            $this->documentElement()->selectFieldOption($selector, (string) $field->getAttribute('value'));

            return $this;
        }

        $this->documentElement()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function uncheckField(string $selector): self
    {
        $this->documentElement()->uncheckField($selector);

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
        $this->documentElement()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->documentElement()->selectFieldOption($selector, $value, true);
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

        $this->documentElement()->attachFileToField($selector, $filename);

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
        $element = $this->documentElement()->findButton($selector);

        if (!$element) {
            // try link
            $element = $this->documentElement()->findLink($selector);
        }

        if (!$element) {
            // try by css
            $element = $this->documentElement()->find('css', $selector);
        }

        if (!$element) {
            Assert::fail('Clickable element "%s" not found.', [$selector]);
        }

        if (!$element->isVisible()) {
            Assert::fail('Clickable element "%s" is not visible.', [$selector]);
        }

        if ($button = $this->documentElement()->findButton($selector)) {
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
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueEquals($selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertFieldNotEquals(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueNotEquals($selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
        } catch (ExpectationException $e) {
            Assert::fail($e->getMessage());
        }

        Assert::that((array) $field->getValue())->contains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
        } catch (ExpectationException $e) {
            Assert::fail($e->getMessage());
        }

        Assert::that((array) $field->getValue())->doesNotContain($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxChecked($selector)
        );
    }

    /**
     * @return static
     */
    final public function assertNotChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxNotChecked($selector)
        );
    }
}
