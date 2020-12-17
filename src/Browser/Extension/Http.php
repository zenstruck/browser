<?php

namespace Zenstruck\Browser\Extension;

use PHPUnit\Framework\Assert as PHPUnit;
use Zenstruck\Browser\HttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Http
{
    /**
     * @param HttpOptions|array $options @see HttpOptions::DEFAULT_OPTIONS
     *
     * @return static
     */
    abstract public function request(string $method, string $url, $options = []): self;

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
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 200 && $status < 300, "Expected successful status code (2xx), [{$status}] received.");

        return $this;
    }

    /**
     * @return static
     */
    final public function assertRedirected(): self
    {
        $status = $this->minkSession()->getStatusCode();

        PHPUnit::assertTrue($status >= 300 && $status < 400, "Expected redirect status code (3xx), [{$status}] received.");

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
}
