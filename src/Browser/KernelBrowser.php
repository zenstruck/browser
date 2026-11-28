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
use Zenstruck\Browser\Internal\ApiMethods;
use Zenstruck\Browser\Internal\KernelBrowserBehavior;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Options from HttpOptions
 *
 * @method SymfonyKernelBrowser client()
 */
abstract class KernelBrowser extends Browser
{
    use ApiMethods {
        setDefaultHttpOptions as private apiSetDefaultHttpOptions;
        request as private apiRequest;
        assertJson as private apiAssertJson;
        assertXml as private apiAssertXml;
        assertJsonMatches as private apiAssertJsonMatches;
        json as private apiJson;
    }
    use Htmlable {
        useParameters as private htmlUseParameters;
    }
    use KernelBrowserBehavior;

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function setDefaultHttpOptions($options): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiSetDefaultHttpOptions($options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @param HttpOptions|array $options HttpOptions::DEFAULT_OPTIONS
     *
     * @return static
     */
    final public function request(string $method, string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest($method, $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function get(string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest('GET', $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function post(string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest('POST', $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function put(string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest('PUT', $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function patch(string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest('PATCH', $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function delete(string $url, $options = []): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiRequest('DELETE', $url, $options);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @return static
     */
    final public function assertJson(): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiAssertJson();
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @return static
     */
    final public function assertXml(): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiAssertXml();
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     *
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiAssertJsonMatches($expression, $expected);
    }

    /**
     * @deprecated since 1.16, use ApiBrowser instead
     */
    final public function json(): Json
    {
        trigger_deprecation('zenstruck/browser', '1.16', '%s::%s() is deprecated, use ->api()->%s() instead.', self::class, __FUNCTION__, __FUNCTION__);

        return $this->apiJson();
    }

    protected function useParameters(): array
    {
        return [...$this->htmlUseParameters(), ...$this->apiParameters()];
    }
}
