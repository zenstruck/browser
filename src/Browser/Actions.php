<?php

namespace Zenstruck\Browser;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Session;
use Symfony\Component\BrowserKit\AbstractBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method AbstractBrowser inner()
 * @method Session         minkSession()
 * @method DocumentElement documentElement()
 */
trait Actions
{
    final public function visit(string $uri): self
    {
        $this->minkSession()->visit($uri);

        return $this;
    }

    final public function get(string $url, array $parameters = [], array $files = [], array $server = []): self
    {
        $this->inner()->request('GET', $url, $parameters, $files, $server);

        return $this;
    }

    final public function post(string $url, array $parameters = [], array $files = [], array $server = []): self
    {
        $this->inner()->request('POST', $url, $parameters, $files, $server);

        return $this;
    }

    final public function put(string $url, array $parameters = [], array $files = [], array $server = []): self
    {
        $this->inner()->request('PUT', $url, $parameters, $files, $server);

        return $this;
    }

    final public function delete(string $url, array $parameters = []): self
    {
        $this->inner()->request('DELETE', $url, $parameters);

        return $this;
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
