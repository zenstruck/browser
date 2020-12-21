<?php

namespace Zenstruck\Browser\Extension;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Zenstruck\Browser\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Html
{
    /**
     * @return static
     */
    final public function follow(string $link): self
    {
        $this->documentElement()->clickLink($link);

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
    public function checkField(string $selector): self
    {
        $this->documentElement()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    public function uncheckField(string $selector): self
    {
        $this->documentElement()->uncheckField($selector);

        return $this;
    }

    /**
     * @return static
     */
    public function selectFieldOption(string $selector, string $value): self
    {
        $this->documentElement()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->documentElement()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function attachFile(string $selector, string $path): self
    {
        $this->documentElement()->attachFileToField($selector, $path);

        return $this;
    }

    /**
     * @return static
     */
    final public function click(string $selector): self
    {
        try {
            $this->documentElement()->pressButton($selector);
        } catch (ElementNotFoundException $e) {
            // try link
            $this->documentElement()->clickLink($selector);
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextContains($expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextNotContains($expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeIn(string $selector, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextContains('css', $selector, $expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextNotContains('css', $selector, $expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeElement(string $selector): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementExists('css', $selector)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeElement(string $selector): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementNotExists('css', $selector)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementCount(string $selector, int $count): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementsCount('css', $selector, $count)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldEquals(string $selector, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueEquals($selector, $expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldNotEquals(string $selector, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueNotEquals($selector, $expected)
        );

        return $this;
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

        Assert::true(
            \in_array($expected, (array) $field->getValue(), true),
            'Expected "%s" to be selected in element "%s" but it was not.',
            $expected,
            $selector
        );

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

        Assert::false(
            \in_array($expected, (array) $field->getValue(), true),
            'Expected "%s" to not be selected in element "%s" but it was.',
            $expected,
            $selector
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertChecked(string $selector): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxChecked($selector)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotChecked(string $selector): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxNotChecked($selector)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeContains('css', $selector, $attribute, $expected)
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeNotContains('css', $selector, $attribute, $expected)
        );

        return $this;
    }
}
