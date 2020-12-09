<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\Extension\Json;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\Test\HasHttpBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;
use Zenstruck\Browser\Tests\Extension\JsonTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserTests, EmailTests, HasHttpBrowser, JsonTests, ProfileAwareTests;

    protected function createEmailBrowser(): HttpBrowser
    {
        $browser = new class(static::createHttpBrowserClient()) extends HttpBrowser {
            use Email;
        };

        $browser->setContainer(static::$container);

        return $browser;
    }

    protected function createJsonBrowser(): HttpBrowser
    {
        return new class(static::createHttpBrowserClient()) extends HttpBrowser {
            use Json;
        };
    }

    protected static function browserClass(): string
    {
        return HttpBrowser::class;
    }
}
