<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Internal;

use Zenstruck\Browser;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Json;
use Zenstruck\Callback\Parameter;

/**
 * @internal
 *
 * @phpstan-require-extends Browser
 * @phpstan-import-type Options from HttpOptions
 */
trait ApiMethods
{
    protected ?HttpOptions $defaultHttpOptions = null;

    /**
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    public function setDefaultHttpOptions($options): self
    {
        $this->defaultHttpOptions = HttpOptions::create($options);

        return $this;
    }

    /**
     * @param HttpOptions|array $options HttpOptions::DEFAULT_OPTIONS
     *
     * @return static
     */
    public function request(string $method, string $url, $options = []): self
    {
        if ($this->defaultHttpOptions) {
            // clone to avoid HttpOptions::merge() mutating the default options
            $options = (clone $this->defaultHttpOptions)->merge($options);
        }

        $options = HttpOptions::create($options);

        $this->session()->request($method, $options->addQueryToUrl($url), $options);

        return $this;
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function get(string $url, $options = []): self
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function post(string $url, $options = []): self
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function put(string $url, $options = []): self
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function patch(string $url, $options = []): self
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function delete(string $url, $options = []): self
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * @return static
     */
    public function assertJson(): self
    {
        return $this->assertContentType('json');
    }

    /**
     * @return static
     */
    public function assertXml(): self
    {
        return $this->assertContentType('xml');
    }

    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    public function assertJsonMatches(string $expression, $expected): self
    {
        $this->assertContentType('json');
        $session = $this->session();
        $json = $session->json();
        $json->assertMatches($expression, $expected);

        return $this;
    }

    public function json(): Json
    {
        $this->assertContentType('json');
        $session = $this->session();

        return $session->json();
    }

    protected function apiParameters(): array
    {
        return [
            Parameter::typed(Json::class, Parameter::factory(fn() => $this->json())),
        ];
    }
}
