<?php

namespace Zenstruck\Browser;

use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Html;
use Zenstruck\Browser\Extension\Http;
use Zenstruck\Browser\Extension\Http\HttpOptions;
use Zenstruck\Browser\Extension\Json;
use Zenstruck\Browser\Mink\BrowserKitDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BrowserKitBrowser extends Browser
{
    use Html, Http, Json;

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

    /**
     * @return static
     */
    final public function interceptRedirects(): self
    {
        $this->inner->followRedirects(false);

        return $this;
    }

    /**
     * @return static
     */
    final public function followRedirect(): self
    {
        $this->inner->followRedirect();

        return $this;
    }

    /**
     * @return static
     */
    final public function assertRedirectedTo(string $expected): self
    {
        $this->assertRedirected();
        $this->followRedirect();
        $this->assertOn($expected);

        return $this;
    }

    /**
     * @see Http::request()
     */
    final public function request(string $method, string $url, $options = []): self
    {
        $options = HttpOptions::create($options);

        $this->inner->request($method, $url, $options->parameters(), $options->files(), $options->server(), $options->body());

        return $this;
    }
}
