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
    final public function followRedirects(): self
    {
        $this->inner->followRedirects(true);

        return $this;
    }

    /**
     * @param int $max The maximum number of redirects to follow (defaults to "infinite")
     *
     * @return static
     */
    final public function followRedirect(int $max = PHP_INT_MAX): self
    {
        for ($i = 0; $i < $max; ++$i) {
            $status = $this->minkSession()->getStatusCode();

            if ($status < 300 || $status > 400) {
                break;
            }

            $this->inner->followRedirect();
        }

        return $this;
    }

    /**
     * @param int $max The maximum number of redirects to follow (defaults to "infinite")
     *
     * @return static
     */
    final public function assertRedirectedTo(string $expected, int $max = PHP_INT_MAX): self
    {
        $this->assertRedirected();
        $this->followRedirect($max);
        $this->assertOn($expected);

        return $this;
    }

    final protected function makeRequest(string $method, string $url, HttpOptions $options): void
    {
        $this->inner->request($method, $url, $options->parameters(), $options->files(), $options->server(), $options->body());
    }
}
