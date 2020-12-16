<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\Test\HasHttpBrowser;
use Zenstruck\Browser\Tests\Component\EmailTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserKitBrowserTests, BrowserTests, EmailTests, HasHttpBrowser, ProfileAwareTests;

    protected static function browserClass(): string
    {
        return HttpBrowser::class;
    }
}
