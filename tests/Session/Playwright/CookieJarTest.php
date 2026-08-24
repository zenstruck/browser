<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Session\Playwright;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Page\PageInterface;
use Symfony\Component\BrowserKit\Response;
use Zenstruck\Browser\Session\Playwright\CookieJar;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CookieJarTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider unsupportedMethodProvider
     */
    #[Test]
    #[DataProvider('unsupportedMethodProvider')]
    public function methods_the_browser_cannot_back_throw(string $method, callable $call): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage($method.'() is not supported by the real browser.');

        $call(new CookieJar($this->createStub(PageInterface::class)));
    }

    public static function unsupportedMethodProvider(): iterable
    {
        // these all rely on jar-local state the real browser does not expose
        yield 'allValues' => ['allValues', static fn(CookieJar $jar) => $jar->allValues('http://localhost')];
        yield 'allRawValues' => ['allRawValues', static fn(CookieJar $jar) => $jar->allRawValues('http://localhost')];
        yield 'updateFromSetCookie' => ['updateFromSetCookie', static fn(CookieJar $jar) => $jar->updateFromSetCookie(['mine=value'])];
        yield 'updateFromResponse' => ['updateFromResponse', static fn(CookieJar $jar) => $jar->updateFromResponse(new Response())];
        yield 'flushExpiredCookies' => ['flushExpiredCookies', static fn(CookieJar $jar) => $jar->flushExpiredCookies()];
    }
}
