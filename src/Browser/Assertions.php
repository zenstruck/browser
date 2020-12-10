<?php

namespace Zenstruck\Browser;

use Behat\Mink\Exception\ExpectationException;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Assertions
{
    /**
     * @return static
     */
    final public function assertOn(string $expected): self
    {
        $expected = \parse_url($expected);
        $actual = \parse_url(\urldecode($this->minkSession()->getCurrentUrl()));

        unset(
            $expected['host'],
            $expected['scheme'],
            $expected['port'],
            $actual['host'],
            $actual['scheme'],
            $actual['port']
        );

        PHPUnit::assertSame($expected, $actual);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertStatus(int $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->statusCodeEquals($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSuccessful(): self
    {
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 200 && $status < 300, "Expected successful status code (2xx), [{$status}] received.");

        return $this;
    }

    /**
     * @return static
     */
    final public function assertRedirected(): self
    {
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 300 && $status < 400, "Expected redirect status code (3xx), [{$status}] received.");

        return $this;
    }

    /**
     * @return static
     */
    final public function assertResponseContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertResponseNotContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseNotContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertHeaderEquals(string $header, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseHeaderEquals($header, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertHeaderContains(string $header, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseHeaderContains($header, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextNotContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextContains('css', $selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextNotContains('css', $selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementExists('css', $selector)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementNotExists('css', $selector)
        );
    }

    /**
     * @return static
     */
    final public function assertElementCount(string $selector, int $count): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementsCount('css', $selector, $count)
        );
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
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        PHPUnit::assertContains($expected, (array) $field->getValue());

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        PHPUnit::assertNotContains($expected, (array) $field->getValue());

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

    /**
     * @return static
     */
    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeContains('css', $selector, $attribute, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeNotContains('css', $selector, $attribute, $expected)
        );
    }

    /**
     * @return static
     */
    final protected function wrapMinkExpectation(callable $callback): self
    {
        try {
            $callback();
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }
}
