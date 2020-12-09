<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\Test\HasHttpBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserTests, EmailTests, HasHttpBrowser, ProfileAwareTests;

    protected function createEmailBrowser(): HttpBrowser
    {
        $browser = new class(static::createHttpBrowserClient()) extends HttpBrowser {
            use Email;
        };

        $browser->setContainer(static::$container);

        return $browser;
    }

    protected static function browserClass(): string
    {
        return HttpBrowser::class;
    }
}
