<?php

namespace Zenstruck\Browser;

use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Mink\BrowserKitDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BrowserKitBrowser extends Browser
{
    private AbstractBrowser $inner;
    private ?HttpOptions $defaultHttpOptions = null;

    public function __construct(AbstractBrowser $inner)
    {
        $this->inner = $inner;

        parent::__construct(new BrowserKitDriver($inner));
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

        if ($this->minkSession()->isStarted() && $this->response()->isRedirect()) {
            $this->followRedirect();
        }

        return $this;
    }

    /**
     * @param int $max The maximum number of redirects to follow (defaults to "infinite")
     *
     * @return static
     */
    final public function followRedirect(int $max = \PHP_INT_MAX): self
    {
        for ($i = 0; $i < $max; ++$i) {
            if (!$this->response()->isRedirect()) {
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
    final public function assertRedirectedTo(string $expected, int $max = \PHP_INT_MAX): self
    {
        $this->assertRedirected();
        $this->followRedirect($max);
        $this->assertOn($expected);

        return $this;
    }

    /**
     * @param HttpOptions|array $options
     *
     * @return static
     */
    final public function setDefaultHttpOptions($options): self
    {
        $this->defaultHttpOptions = HttpOptions::create($options);

        return $this;
    }

    /**
     * @param HttpOptions|array $options HttpOptions::DEFAULT_OPTIONS
     *
     * @return static
     */
    final public function request(string $method, string $url, $options = []): self
    {
        if ($this->defaultHttpOptions) {
            $options = $this->defaultHttpOptions->merge($options);
        }

        $options = HttpOptions::create($options);

        $this->inner->request(
            $method,
            $options->addQueryToUrl($url),
            $options->parameters(),
            $options->files(),
            $options->server(),
            $options->body()
        );

        return $this;
    }

    /**
     * @see request()
     *
     * @return static
     */
    final public function get(string $url, $options = []): self
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @see request()
     *
     * @return static
     */
    final public function post(string $url, $options = []): self
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * @see request()
     *
     * @return static
     */
    final public function put(string $url, $options = []): self
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * @see request()
     *
     * @return static
     */
    final public function delete(string $url, $options = []): self
    {
        return $this->request('DELETE', $url, $options);
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
        Assert::true($this->response()->isSuccessful(), 'Expected successful status code (2xx) but got {actual}.', [
            'actual' => $this->response()->statusCode(),
        ]);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertRedirected(): self
    {
        if ($this->inner->isFollowingRedirects()) {
            throw new \RuntimeException('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');
        }

        Assert::true($this->response()->isRedirect(), 'Expected redirect status code (3xx) but got {actual}.', [
            'actual' => $this->response()->statusCode(),
        ]);

        return $this;
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
    final public function assertJson(string $expectedContentType = 'json'): self
    {
        return $this->assertHeaderContains('Content-Type', $expectedContentType);
    }

    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        Assert::that($this->response()->assertJson()->search($expression))->is($expected);

        return $this;
    }

    abstract public function profile(): Profile;

    final protected function inner(): AbstractBrowser
    {
        return $this->inner;
    }
}
