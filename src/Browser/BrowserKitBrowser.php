<?php

namespace Zenstruck\Browser;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BrowserKitBrowser extends Browser implements ProfileAware
{
    private AbstractBrowser $inner;

    public function __construct(AbstractBrowser $inner)
    {
        $this->inner = $inner;

        parent::__construct(new BrowserKitDriver($inner));
    }

    final public function inner(): AbstractBrowser
    {
        return $this->inner;
    }

    final public function interceptRedirects(): self
    {
        $this->inner->followRedirects(false);

        return $this;
    }

    final public function followRedirect(): self
    {
        $this->inner->followRedirect();

        return $this;
    }

    final public function assertRedirectedTo(string $expected): self
    {
        $this->assertRedirected();
        $this->followRedirect();
        $this->assertOn($expected);

        return $this;
    }

    /**
     * @param HttpOptions|array $options @see HttpOptions::DEFAULT_OPTIONS
     */
    final public function request(string $method, string $url, $options = []): self
    {
        $options = HttpOptions::create($options);

        $this->inner->request($method, $url, $options->parameters(), $options->files(), $options->server(), $options->body());

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
}
