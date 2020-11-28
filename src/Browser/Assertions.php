<?php

namespace Zenstruck\Browser;

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\WebAssert;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method WebAssert webAssert()
 */
trait Assertions
{
    final public function assertStatus(int $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->statusCodeEquals($expected)
        );
    }

    final public function assertSuccessful(): self
    {
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 200 && $status < 300, "Expected successful status code (2xx), [{$status}] received.");

        return $this;
    }

    final public function assertRedirected(): self
    {
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 300 && $status < 400, "Expected redirect status code (3xx), [{$status}] received.");

        return $this;
    }

    final public function assertRedirectedTo(string $expected): self
    {
        $this->assertRedirected();
        $this->followRedirect();
        $this->assertOn($expected);

        return $this;
    }

    final public function assertResponseContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseContains($expected)
        );
    }

    final public function assertResponseNotContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseNotContains($expected)
        );
    }

    final public function assertSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextContains($expected)
        );
    }

    final public function assertNotSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextNotContains($expected)
        );
    }

    final public function assertSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextContains('css', $selector, $expected)
        );
    }

    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextNotContains('css', $selector, $expected)
        );
    }

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

    final public function assertSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementExists('css', $selector)
        );
    }

    final public function assertNotSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementNotExists('css', $selector)
        );
    }

    final public function assertElementCount(string $selector, int $count): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementsCount('css', $selector, $count)
        );
    }

    final public function assertFieldContains(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueEquals($selector, $expected)
        );
    }

    final public function assertChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxChecked($selector)
        );
    }

    final public function assertNotChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxNotChecked($selector)
        );
    }

    final public function assertHeader(string $header, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseHeaderEquals($header, $expected)
        );
    }

    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeContains('css', $selector, $attribute, $expected)
        );
    }

    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeNotContains('css', $selector, $attribute, $expected)
        );
    }

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
