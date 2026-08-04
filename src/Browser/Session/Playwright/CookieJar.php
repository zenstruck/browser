<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session\Playwright;

use Playwright\Browser\BrowserContextInterface;
use Playwright\Page\PageInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar as BrowserKitCookieJar;
use Symfony\Component\BrowserKit\Response;

/**
 * The real browser holds the cookies, so mutating the client's jar has no effect. This proxies to
 * the browser instead, as {@see \Symfony\Component\Panther\Cookie\CookieJar} does for WebDriver.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class CookieJar extends BrowserKitCookieJar
{
    public function __construct(private readonly PageInterface $page)
    {
    }

    public function set(Cookie $cookie): void
    {
        $data = [
            'name' => $cookie->getName(),
            'value' => (string) $cookie->getValue(),
            'secure' => $cookie->isSecure(),
            'httpOnly' => $cookie->isHttpOnly(),
        ];

        // playwright needs a domain/path pair: passing a url instead makes it derive the path from
        // that url, which silently widens a cookie scoped to something narrower
        $data['domain'] = $cookie->getDomain() ?: (string) \parse_url($this->page->url(), \PHP_URL_HOST);
        $data['path'] = $cookie->getPath();

        if (null !== $expires = $cookie->getExpiresTime()) {
            $data['expires'] = (int) $expires;
        }

        if ($sameSite = $cookie->getSameSite()) {
            $data['sameSite'] = \ucfirst($sameSite);
        }

        $this->context()->addCookies([$data]);
    }

    public function get(string $name, string $path = '/', ?string $domain = null): ?Cookie
    {
        foreach ($this->all() as $cookie) {
            if ($name !== $cookie->getName() || !\str_starts_with($path, $cookie->getPath())) {
                continue;
            }

            if (null === $domain || '' === $cookie->getDomain() || \str_ends_with('.'.$domain, '.'.\ltrim($cookie->getDomain(), '.'))) {
                return $cookie;
            }
        }

        return null;
    }

    public function expire(string $name, ?string $path = '/', ?string $domain = null): void
    {
        $this->context()->clearCookies(['name' => $name]); // @phpstan-ignore argument.type
    }

    public function clear(): void
    {
        $this->context()->clearCookies();
    }

    /**
     * @return Cookie[]
     */
    public function all(): array
    {
        return \array_map(
            static fn(array $cookie) => new Cookie(
                name: $cookie['name'],
                value: $cookie['value'],
                // playwright reports this as a float, BrowserKit wants an integer timestamp
                expires: $cookie['expires'] > 0 ? (string) (int) $cookie['expires'] : null,
                path: $cookie['path'],
                domain: $cookie['domain'],
                secure: $cookie['secure'],
                httponly: $cookie['httpOnly'],
                // playwright capitalizes it, BrowserKit round-trips whatever it was given
                samesite: \mb_strtolower($cookie['sameSite']),
            ),
            $this->context()->cookies(),
        );
    }

    public function updateFromSetCookie(array $setCookies, ?string $uri = null): void
    {
        throw self::notSupported(__FUNCTION__);
    }

    public function updateFromResponse(Response $response, ?string $uri = null): void
    {
        throw self::notSupported(__FUNCTION__);
    }

    public function allValues(string $uri, bool $returnsRawValue = false): array
    {
        throw self::notSupported(__FUNCTION__);
    }

    public function allRawValues(string $uri): array
    {
        throw self::notSupported(__FUNCTION__);
    }

    public function flushExpiredCookies(): void
    {
        throw self::notSupported(__FUNCTION__);
    }

    private function context(): BrowserContextInterface
    {
        return $this->page->context();
    }

    private static function notSupported(string $function): \BadMethodCallException
    {
        return new \BadMethodCallException(\sprintf('%s::%s() is not supported by the real browser.', self::class, $function));
    }
}
