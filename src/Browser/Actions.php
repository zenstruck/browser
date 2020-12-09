<?php

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Actions
{
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
    final public function press(string $selector): self
    {
        $this->documentElement()->pressButton($selector);

        return $this;
    }
}
