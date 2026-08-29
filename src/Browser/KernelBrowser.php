<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Zenstruck\Browser;
use Zenstruck\Browser\Session\Driver\BrowserKitDriver;
use Zenstruck\Callback\Parameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Options from HttpOptions
 *
 * @method SymfonyKernelBrowser client()
 */
class KernelBrowser extends Browser
{
    protected ?HttpOptions $defaultHttpOptions = null;

    /**
     * @internal
     */
    final public function __construct(SymfonyKernelBrowser $client, array $options = [])
    {
        $client->followRedirects((bool) ($options['follow_redirects'] ?? true));

        parent::__construct(new BrowserKitDriver($client), $options); // @phpstan-ignore argument.type

        if (!($options['catch_exceptions'] ?? true)) {
            $this->throwExceptions();
        }
    }

    /**
     * @see SymfonyKernelBrowser::disableReboot()
     *
     * @return static
     */
    final public function disableReboot(): self
    {
        $this->client()->disableReboot();

        return $this;
    }

    /**
     * @see SymfonyKernelBrowser::enableReboot()
     *
     * @return static
     */
    final public function enableReboot(): self
    {
        $this->client()->enableReboot();

        return $this;
    }

    /**
     * @param HttpOptions|Options $options
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
    public function delete(string $url, $options = []): self
    {
        return $this->request('DELETE', $url, $options);
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
     * @return static
     */
    final public function assertJson(): self
    {
        return $this->assertContentType('json');
    }

    /**
     * @return static
     */
    final public function assertXml(): self
    {
        return $this->assertContentType('xml');
    }

    /**
     * @return static
     */
    final public function assertHtml(): self
    {
        return $this->assertContentType('html');
    }

    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        $this->json()->assertMatches($expression, $expected);

        return $this;
    }

    final public function json(): Json
    {
        return $this->assertJson()->session()->json();
    }

    protected function useParameters(): array
    {
        return [
            ...parent::useParameters(),
            Parameter::typed(Json::class, Parameter::factory(fn() => $this->json())),
        ];
    }
}
