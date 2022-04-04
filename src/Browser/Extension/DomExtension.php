<?php

namespace Zenstruck\Browser\Extension;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait DomExtension
{
    final public function crawler(): Crawler
    {
        return $this->client()->getCrawler();
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
}
