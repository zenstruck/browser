<?php

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Actions
{
    final public function visit(string $uri): self
    {
        $this->minkSession()->visit($uri);

        return $this;
    }

    /**
     * @param HttpOptions|array $options @see HttpOptions::DEFAULT_OPTIONS
     */
    final public function request(string $method, string $url, $options = []): self
    {
        $options = HttpOptions::create($options);

        $this->inner()->request($method, $url, $options->parameters(), $options->files(), $options->server(), $options->body());

        return $this;
    }

    /**
     * @see request()
     */
    final public function get(string $url, $options = []): self
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @see request()
     */
    final public function post(string $url, $options = []): self
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * @see request()
     */
    final public function put(string $url, $options = []): self
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * @see request()
     */
    final public function delete(string $url, $options = []): self
    {
        return $this->request('DELETE', $url, $options);
    }

    final public function follow(string $link): self
    {
        $this->documentElement()->clickLink($link);

        return $this;
    }

    final public function fillField(string $selector, string $value): self
    {
        $this->documentElement()->fillField($selector, $value);

        return $this;
    }

    public function checkField(string $selector): self
    {
        $this->documentElement()->checkField($selector);

        return $this;
    }

    public function uncheckField(string $selector): self
    {
        $this->documentElement()->uncheckField($selector);

        return $this;
    }

    public function selectFieldOption(string $selector, string $value): self
    {
        $this->documentElement()->selectFieldOption($selector, $value);

        return $this;
    }

    public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->documentElement()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    final public function attachFile(string $selector, string $path): self
    {
        $this->documentElement()->attachFileToField($selector, $path);

        return $this;
    }

    final public function press(string $selector): self
    {
        $this->documentElement()->pressButton($selector);

        return $this;
    }

    final public function followRedirect(): self
    {
        $this->inner()->followRedirect();

        return $this;
    }
}
