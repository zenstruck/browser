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
    public function assertStatus(int $expected): self
    {
        try {
            $this->webAssert()->statusCodeEquals($expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertSuccessful(): self
    {
        PHPUnit::assertTrue($this->minkSession()->getStatusCode() >= 200 && $this->minkSession()->getStatusCode() < 300);

        return $this;
    }

    public function assertRedirected(): self
    {
        PHPUnit::assertTrue($this->minkSession()->getStatusCode() >= 300 && $this->minkSession()->getStatusCode() < 400);

        return $this;
    }

    public function assertRedirectedTo(string $expected): self
    {
        $this->assertRedirected();
        $this->followRedirect();
        $this->assertOn($expected);
        $this->assertSuccessful();

        return $this;
    }

    public function assertResponseContains(string $expected): self
    {
        try {
            $this->webAssert()->responseContains($expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertResponseNotContains(string $expected): self
    {
        try {
            $this->webAssert()->responseNotContains($expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertSee(string $expected): self
    {
        try {
            $this->webAssert()->pageTextContains($expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertNotSee(string $expected): self
    {
        try {
            $this->webAssert()->pageTextNotContains($expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertSeeIn(string $selector, string $expected): self
    {
        try {
            $this->webAssert()->elementTextContains('css', $selector, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertNotSeeIn(string $selector, string $expected): self
    {
        try {
            $this->webAssert()->elementTextNotContains('css', $selector, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertOn(string $expected): self
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

    public function assertSeeElement(string $selector): self
    {
        try {
            $this->webAssert()->elementExists('css', $selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertNotSeeElement(string $selector): self
    {
        try {
            $this->webAssert()->elementNotExists('css', $selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertElementCount(string $selector, int $count): self
    {
        try {
            $this->webAssert()->elementsCount('css', $selector, $count);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertFieldContains(string $selector, string $expected): self
    {
        try {
            $this->webAssert()->fieldValueEquals($selector, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertChecked(string $selector): self
    {
        try {
            $this->webAssert()->checkboxChecked($selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertNotChecked(string $selector): self
    {
        try {
            $this->webAssert()->checkboxNotChecked($selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertHeader(string $header, string $expected): self
    {
        try {
            $this->webAssert()->responseHeaderEquals($header, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        try {
            $this->webAssert()->elementAttributeContains('css', $selector, $attribute, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }

    public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        try {
            $this->webAssert()->elementAttributeNotContains('css', $selector, $attribute, $expected);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }
}
